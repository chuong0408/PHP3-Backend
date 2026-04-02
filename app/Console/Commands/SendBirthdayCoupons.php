<?php

namespace App\Console\Commands;

use App\Mail\BirthdayCouponMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendBirthdayCoupons extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'birthday:send-coupons
                            {--discount=10% : Phần trăm giảm giá (vd: 20%)}
                            {--dry-run : Chỉ hiển thị danh sách, không gửi email}';

    /**
     * The console command description.
     */
    protected $description = 'Gửi email chúc mừng sinh nhật kèm mã giảm giá cho khách hàng có sinh nhật hôm nay';

    public function handle(): int
    {
        $today = now();
        $todayMonth = $today->month;
        $todayDay   = $today->day;
        $discount   = $this->option('discount');
        $isDryRun   = $this->option('dry-run');

        $this->info("🎂 Đang tìm khách hàng có sinh nhật hôm nay ({$today->format('d/m')})...");

        // Lấy danh sách user có sinh nhật hôm nay, chưa nhận coupon sinh nhật năm nay
        $users = User::whereNotNull('birthday')
            ->whereMonth('birthday', $todayMonth)
            ->whereDay('birthday', $todayDay)
            ->where('status', 1)
            ->whereNotExists(function ($query) use ($today) {
                // Kiểm tra user chưa nhận birthday coupon trong năm nay
                $query->select(DB::raw(1))
                    ->from('coupon')
                    ->join('coupon_details', 'coupon.coupon_code', '=', 'coupon_details.coupon_code')
                    ->whereColumn('coupon.user_id', 'user.id')
                    ->where('coupon_details.is_birthday_coupon', true)
                    ->whereYear('coupon_details.created_at', $today->year);
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('✅ Không có khách hàng nào có sinh nhật hôm nay hoặc tất cả đã nhận coupon.');
            return self::SUCCESS;
        }

        $this->info("👥 Tìm thấy {$users->count()} khách hàng:");

        $table = $users->map(fn($u) => [
            'ID'       => $u->id,
            'Họ tên'   => $u->fullname,
            'Email'    => $u->email,
            'Sinh nhật' => $u->birthday,
        ])->toArray();

        $this->table(['ID', 'Họ tên', 'Email', 'Sinh nhật'], $table);

        if ($isDryRun) {
            $this->warn('⚠️  Chế độ dry-run: không gửi email thực.');
            return self::SUCCESS;
        }

        $sent    = 0;
        $failed  = 0;
        $expires = now()->endOfDay(); // Coupon hết hạn cuối ngày sinh nhật

        foreach ($users as $user) {
            try {
                DB::transaction(function () use ($user, $discount, $expires, &$sent) {
                    // Sinh mã coupon độc nhất
                    $couponCode = 'BDAY-' . strtoupper(Str::random(6)) . '-' . $user->id;

                    // Tạo coupon_details
                    DB::table('coupon_details')->insert([
                        'coupon_code'       => $couponCode,
                        'discount'          => $discount,
                        'description'       => 'Mã giảm giá sinh nhật dành riêng cho ' . $user->fullname,
                        'minordervalue'     => 0,
                        'created_at'        => now(),
                        'expires_at'        => $expires,
                        'is_birthday_coupon' => true,
                    ]);

                    // Gán coupon cho user
                    DB::table('coupon')->insert([
                        'user_id'     => $user->id,
                        'coupon_code' => $couponCode,
                    ]);

                    // Gửi email
                    Mail::to($user->email)->send(new BirthdayCouponMail(
                        user: $user,
                        couponCode: $couponCode,
                        discount: $discount,
                        expiresAt: $expires->format('d/m/Y H:i'),
                    ));

                    $sent++;
                    $this->line("  ✅ Đã gửi → {$user->fullname} ({$user->email}) | Mã: {$couponCode}");
                });
            } catch (\Throwable $e) {
                $failed++;
                $this->error("  ❌ Thất bại → {$user->fullname} ({$user->email}): {$e->getMessage()}");
                Log::error('SendBirthdayCoupons: ' . $e->getMessage(), [
                    'user_id' => $user->id,
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info("🎉 Hoàn tất! Đã gửi: {$sent} | Thất bại: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}