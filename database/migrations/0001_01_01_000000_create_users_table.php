<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('merchantcode', 250)->nullable();
            $table->string('name', 191);
            $table->string('email', 191);
            $table->string('mobile', 10);
            $table->string('password', 191);
            $table->string('remember_token', 191)->nullable();
            $table->string('otpverify', 250)->default('yes');
            $table->integer('otpresend')->default(0);
            $table->double('mainwallet', 21, 2)->default(0.00);
            $table->double('lockedamount', 11, 2)->default(0.00);
            $table->integer('role_id');
            $table->integer('parent_id')->default(0);
            $table->integer('company_id')->nullable();
            $table->integer('scheme_id')->nullable();
            $table->enum('status', ['active', 'block'])->default('active');
            $table->longText('address')->nullable();
            $table->string('shopname', 191)->nullable();
            $table->string('gstin', 191)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('city', 191)->nullable();
            $table->string('state', 191)->nullable();
            $table->string('pincode', 6)->nullable();
            $table->string('pancard', 191)->nullable();
            $table->string('aadharcard', 12)->nullable();
            $table->longText('pancardpic')->nullable();
            $table->longText('aadharcardpic')->nullable();
            $table->longText('gstpic')->nullable();
            $table->longText('profile')->nullable();
            $table->longText('profilepic')->nullable();
            $table->enum('kyc', ['pending', 'submitted', 'verified', 'rejected'])->default('pending');
            $table->longText('callbackurl')->nullable();
            $table->longText('remark')->nullable();
            $table->enum('resetpwd', ['default', 'changed'])->default('default');
            $table->string('bank_holder_name', 250)->nullable();
            $table->string('account', 250)->nullable();
            $table->string('bank', 250)->nullable();
            $table->string('ifsc', 250)->nullable();
            $table->string('passwordold', 250)->nullable();
            $table->timestamps();
            $table->softDeletes();
           
           
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
