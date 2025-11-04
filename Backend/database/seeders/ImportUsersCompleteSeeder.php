<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Salon;
use App\Models\SalonSmsBalance;
use App\Models\SmsTransaction;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\BusinessCategory;
use App\Models\BusinessSubcategory;
use App\Models\Province;
use App\Models\City;
use Carbon\Carbon;

class ImportUsersCompleteSeeder extends Seeder
{
    private $forceImport = false;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // بررسی آرگومان force
        if ($this->command->option('force')) {
            $this->forceImport = true;
        }

         $jsonFile = base_path('exported_users_nov3_complete_2025-11-05_00-05-18.json');
        
        if (!file_exists($jsonFile)) {
            $this->command->error("فایل JSON یافت نشد: {$jsonFile}");
            return;
        }

        $this->command->info("شروع import داده‌ها از فایل JSON...");
        
        // خواندن و decode کردن فایل JSON
        $jsonContent = file_get_contents($jsonFile);
        $usersData = json_decode($jsonContent, true);
        
        if (!$usersData) {
            $this->command->error("خطا در خواندن فایل JSON");
            return;
        }

        $this->command->info("تعداد کاربران برای import: " . count($usersData));

        DB::beginTransaction();
        
        try {
            $importedUsers = 0;
            $importedSalons = 0;
            $importedCustomers = 0;
            $importedStaff = 0;
            $importedAppointments = 0;

            foreach ($usersData as $userData) {
                // بررسی وجود کاربر
                $existingUser = User::where('mobile', $userData['mobile'])->first();
                
                if ($existingUser && !$this->forceImport) {
                    $this->command->warn("کاربر با موبایل {$userData['mobile']} قبلاً وجود دارد، رد شد.");
                    continue;
                } elseif ($existingUser && $this->forceImport) {
                    // حذف کاربر و اطلاعات مرتبط در صورت force
                    $this->command->info("حذف کاربر موجود: {$userData['mobile']}");
                    $this->deleteUserAndRelatedData($existingUser);
                }

                // ایجاد کاربر جدید
                $user = $this->createUser($userData);
                $importedUsers++;
                
                // import سالن‌های کاربر
                if (!empty($userData['all_salons'])) {
                    foreach ($userData['all_salons'] as $salonData) {
                        $salon = $this->createSalon($user->id, $salonData);
                        $importedSalons++;
                        
                        // تنظیم active_salon_id
                        if ($userData['active_salon_id'] == $salonData['id']) {
                            $user->update(['active_salon_id' => $salon->id]);
                        }
                        
                        // import اعتبار پیامک
                        if (!empty($salonData['sms_balance'])) {
                            $this->createSmsBalance($salon->id, $salonData['sms_balance']);
                        }
                        
                        // import تراکنش‌های پیامک
                        if (!empty($salonData['sms_transactions'])) {
                            foreach ($salonData['sms_transactions'] as $transactionData) {
                                $this->createSmsTransaction($salon->id, $user->id, $transactionData);
                            }
                        }
                        
                        // import پرسنل
                        if (!empty($salonData['staff'])) {
                            foreach ($salonData['staff'] as $staffData) {
                                $this->createStaff($salon->id, $staffData);
                                $importedStaff++;
                            }
                        }
                        
                        // import مشتریان
                        $customerIdMapping = [];
                        if (!empty($salonData['customers'])) {
                            foreach ($salonData['customers'] as $customerData) {
                                $customer = $this->createCustomer($salon->id, $customerData);
                                $customerIdMapping[$customerData['id']] = $customer->id;
                                $importedCustomers++;
                            }
                        }
                        
                        // import نوبت‌ها
                        if (!empty($salonData['appointments'])) {
                            foreach ($salonData['appointments'] as $appointmentData) {
                                $this->createAppointment($salon->id, $appointmentData, $customerIdMapping);
                                $importedAppointments++;
                            }
                        }
                    }
                }
                
                if ($importedUsers % 10 == 0) {
                    $this->command->info("تا کنون {$importedUsers} کاربر import شده است...");
                }
            }

            DB::commit();
            
            $this->command->info("Import با موفقیت انجام شد!");
            $this->command->table(
                ['نوع', 'تعداد'],
                [
                    ['کاربران', $importedUsers],
                    ['سالن‌ها', $importedSalons],
                    ['مشتریان', $importedCustomers],
                    ['پرسنل', $importedStaff],
                    ['نوبت‌ها', $importedAppointments],
                ]
            );

        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error("خطا در import: " . $e->getMessage());
            Log::error("Import error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    /**
     * ایجاد کاربر جدید
     */
    private function createUser(array $userData): User
    {
        return User::create([
            'name' => $userData['name'],
            'mobile' => $userData['mobile'],
            'password' => $userData['password'], // password قبلاً hash شده
            'email' => $userData['email'],
            'business_name' => $userData['business_name'],
            'business_category_id' => $userData['business_category_id'],
            // حذف فیلدهای غیرموجود
            // 'is_verified' => $userData['is_verified'],
            // 'profile_completed' => $userData['profile_completed'],
            'gender' => $userData['gender'],
            'date_of_birth' => $userData['date_of_birth'],
            'last_login_at' => $userData['last_login_at'] ? Carbon::parse($userData['last_login_at']) : null,
            'referral_code' => $userData['referral_code'],
            'referrer_id' => $userData['referrer_id'],
            'wallet_balance' => $userData['wallet_balance'],
            'created_at' => Carbon::parse($userData['created_at']),
            'updated_at' => Carbon::parse($userData['updated_at']),
        ]);
    }

    /**
     * ایجاد سالن جدید
     */
    private function createSalon(int $userId, array $salonData): Salon
    {
        return Salon::create([
            'user_id' => $userId,
            'name' => $salonData['name'],
            'mobile' => $salonData['mobile'],
            'email' => $salonData['email'],
            // 'phone' => $salonData['phone'], // حذف - معادل mobile است
            'address' => $salonData['address'],
            'website' => $salonData['website'],
            'bio' => $salonData['bio'],
            'instagram' => $salonData['instagram'],
            'telegram' => $salonData['telegram'],
            'whatsapp' => $salonData['whatsapp'],
            'lat' => $salonData['lat'],
            'lang' => $salonData['lang'],
            // 'location' => $salonData['location'], // بررسی کنیم این فیلد وجود دارد یا نه
            'image' => $salonData['image'],
            // 'description' => $salonData['description'], // بررسی کنیم این فیلد وجود دارد یا نه
            'is_active' => $salonData['is_active'],
            'credit_score' => $salonData['credit_score'],
            'business_category_id' => $salonData['business_category']['id'] ?? null,
            'province_id' => $salonData['province']['id'] ?? null,
            'city_id' => $salonData['city']['id'] ?? null,
            'created_at' => Carbon::parse($salonData['created_at']),
            'updated_at' => Carbon::parse($salonData['updated_at']),
        ]);
    }

    /**
     * ایجاد اعتبار پیامک سالن
     */
    private function createSmsBalance(int $salonId, array $smsBalanceData): void
    {
        SalonSmsBalance::create([
            'salon_id' => $salonId,
            'balance' => $smsBalanceData['balance'],
            'created_at' => Carbon::parse($smsBalanceData['created_at']),
            'updated_at' => Carbon::parse($smsBalanceData['updated_at']),
        ]);
    }

    /**
     * ایجاد تراکنش پیامک
     */
    private function createSmsTransaction(int $salonId, int $userId, array $transactionData): void
    {
        SmsTransaction::create([
            'salon_id' => $salonId,
            'user_id' => $userId,
            'type' => $transactionData['type'],
            'amount' => $transactionData['amount'],
            'description' => $transactionData['description'],
            'status' => $transactionData['status'],
            'created_at' => Carbon::parse($transactionData['created_at']),
        ]);
    }

    /**
     * ایجاد پرسنل سالن
     */
    private function createStaff(int $salonId, array $staffData): void
    {
        Staff::create([
            'salon_id' => $salonId,
            'full_name' => $staffData['full_name'],
            'specialty' => $staffData['specialty'],
            'phone_number' => $staffData['phone_number'],
            'address' => $staffData['address'],
            'is_active' => $staffData['is_active'],
            'hire_date' => $staffData['hire_date'],
            'total_appointments' => $staffData['total_appointments'],
            'completed_appointments' => $staffData['completed_appointments'],
            'canceled_appointments' => $staffData['canceled_appointments'],
            'total_income' => $staffData['total_income'],
            'created_at' => Carbon::parse($staffData['created_at']),
        ]);
    }

    /**
     * ایجاد مشتری سالن
     */
    private function createCustomer(int $salonId, array $customerData): Customer
    {
        return Customer::create([
            'salon_id' => $salonId,
            'name' => $customerData['name'],
            'phone_number' => $customerData['phone_number'],
            'birth_date' => $customerData['birth_date'] ? Carbon::parse($customerData['birth_date']) : null,
            'gender' => $customerData['gender'],
            'address' => $customerData['address'],
            'notes' => $customerData['notes'],
            'emergency_contact' => $customerData['emergency_contact'],
            'created_at' => Carbon::parse($customerData['created_at']),
        ]);
    }

    /**
     * ایجاد نوبت
     */
    private function createAppointment(int $salonId, array $appointmentData, array $customerIdMapping): void
    {
        // پیدا کردن ID جدید مشتری
        $newCustomerId = $customerIdMapping[$appointmentData['customer_id']] ?? null;
        
        if (!$newCustomerId) {
            Log::warning("مشتری با ID {$appointmentData['customer_id']} برای نوبت یافت نشد");
            return;
        }

        // پیدا کردن پرسنل بر اساس نام (چون staff_id ممکن است تغییر کرده باشد)
        $staffId = null;
        if ($appointmentData['staff']) {
            $staff = Staff::where('salon_id', $salonId)
                ->where('full_name', $appointmentData['staff']['full_name'])
                ->first();
            $staffId = $staff?->id;
        }

        Appointment::create([
            'salon_id' => $salonId,
            'customer_id' => $newCustomerId,
            'staff_id' => $staffId,
            'appointment_date' => Carbon::parse($appointmentData['appointment_date']),
            'start_time' => $appointmentData['start_time'],
            'end_time' => $appointmentData['end_time'],
            'total_price' => $appointmentData['total_price'],
            'total_duration' => $appointmentData['total_duration'],
            'status' => $appointmentData['status'],
            'notes' => $appointmentData['notes'],
            'internal_note' => $appointmentData['internal_note'],
            'deposit_required' => $appointmentData['deposit_required'],
            'deposit_paid' => $appointmentData['deposit_paid'],
            'reminder_sms_sent_at' => $appointmentData['reminder_sms_sent_at'] ? Carbon::parse($appointmentData['reminder_sms_sent_at']) : null,
            'survey_sms_sent_at' => $appointmentData['survey_sms_sent_at'] ? Carbon::parse($appointmentData['survey_sms_sent_at']) : null,
            'created_at' => Carbon::parse($appointmentData['created_at']),
        ]);
    }

    /**
     * حذف کاربر و تمام اطلاعات مرتبط
     */
    private function deleteUserAndRelatedData(User $user): void
    {
        // حذف نوبت‌ها
        foreach ($user->salons as $salon) {
            $salon->appointments()->delete();
            $salon->customers()->delete();
            $salon->staff()->delete();
            $salon->smsTransactions()->delete();
            $salon->smsBalance()->delete();
        }
        
        // حذف سالن‌ها
        $user->salons()->delete();
        
        // حذف کاربر
        $user->delete();
    }
}
