<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GhnService
{
    private string $token;
    private int    $shopId;
    private string $apiUrl;

    public function __construct()
    {
        $this->token  = config('services.ghn.token');
        $this->shopId = (int) config('services.ghn.shop_id');
        $this->apiUrl = config('services.ghn.api_url');
    }

    /**
     * Lấy danh sách tỉnh/thành phố
     */
    public function getProvinces(): array
    {
        $res = Http::withHeaders($this->headers())
            ->get("{$this->apiUrl}/master-data/province");

        return $res->json('data', []);
    }

    /**
     * Lấy danh sách quận/huyện theo tỉnh
     */
    public function getDistricts(int $provinceId): array
    {
        $res = Http::withHeaders($this->headers())
            ->post("{$this->apiUrl}/master-data/district", [
                'province_id' => $provinceId,
            ]);

        return $res->json('data', []);
    }

    /**
     * Lấy danh sách phường/xã theo quận
     */
    public function getWards(int $districtId): array
    {
        $res = Http::withHeaders($this->headers())
            ->post("{$this->apiUrl}/master-data/ward", [
                'district_id' => $districtId,
            ]);

        return $res->json('data', []);
    }

    /**
     * Tính phí ship
     * 
     * @param int $toDistrictId   - District ID của GHN
     * @param string $toWardCode  - Ward Code của GHN
     * @param int $weight         - Trọng lượng (gram)
     * @param int $insuranceValue - Giá trị hàng để tính bảo hiểm
     */
    public function calculateFee(
        int    $toDistrictId,
        string $toWardCode,
        int    $weight = 500,
        int    $insuranceValue = 0
    ): ?int {
        try {
            $res = Http::withHeaders($this->headers(includeShopId: true))
                ->post("{$this->apiUrl}/v2/shipping-order/fee", [
                    'service_type_id'  => 2,          // Giao hàng thường
                    'to_district_id'   => $toDistrictId,
                    'to_ward_code'     => $toWardCode,
                    'weight'           => $weight,
                    'insurance_value'  => $insuranceValue,
                    'coupon'           => null,
                ]);

            if ($res->successful() && $res->json('code') === 200) {
                return (int) $res->json('data.total');
            }

            Log::warning('GHN fee error: ' . $res->body());
            return null;

        } catch (\Throwable $e) {
            Log::error('GHN exception: ' . $e->getMessage());
            return null;
        }
    }

    private function headers(bool $includeShopId = false): array
    {
        $headers = [
            'Token'        => $this->token,
            'Content-Type' => 'application/json',
        ];

        if ($includeShopId) {
            $headers['ShopId'] = $this->shopId;
        }

        return $headers;
    }
}