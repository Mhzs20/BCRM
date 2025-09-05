<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplateCategory;
use Illuminate\Http\Request;

class SmsTemplateCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100']);
        SmsTemplateCategory::create(['name' => $data['name'], 'salon_id' => null]);
        return redirect()->route('admin.sms-templates.index')->with('success','دسته ایجاد شد.');
    }

    public function destroy(SmsTemplateCategory $smsTemplateCategory)
    {
        if (!is_null($smsTemplateCategory->salon_id)) {
            return redirect()->route('admin.sms-templates.index')->with('error','حذف مجاز نیست.');
        }
        $smsTemplateCategory->templates()->update(['category_id' => null]);
        $smsTemplateCategory->delete();
        return redirect()->route('admin.sms-templates.index')->with('success','دسته حذف شد.');
    }
}
