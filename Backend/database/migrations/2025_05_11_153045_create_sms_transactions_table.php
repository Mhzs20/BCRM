<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('sms_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null')->comment('کاربری که هزینه پیامک از او کسر شده');
            $table->foreignId('salon_id')->constrained('salons')->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->string('receptor')->comment('شماره گیرنده پیامک');
            $table->string('sms_type')->comment('نوع پیامک ارسالی');
            $table->text('content')->comment('متن پیامک ارسال شده');
            $table->timestamp('sent_at')->comment('زمان ارسال');
            $table->string('status')->comment('وضعیت ارسال: sent, failed, simulated, error');
            $table->text('external_response')->nullable()->comment('پاسخ دریافتی از پنل پیامک');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_transactions');
    }
}
