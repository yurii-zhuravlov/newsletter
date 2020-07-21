<?php
declare(strict_types=1);

namespace YuriiZh\Newsletter\Service;

use Magento\SalesRule\Model\CouponGenerator;

/**
 * Class GenerateCouponListService
 */
class GenerateCouponCodesService
{
    public const CART_PRICE_RULE_NAME = 'Lito Cart Price Rule';

    /**
     * Coupon Generator
     *
     * @var CouponGenerator
     */
    protected $couponGenerator;

    /**
     * GenerateCouponCodesService constructor
     *
     * @param CouponGenerator $couponGenerator
     */
    public function __construct(CouponGenerator $couponGenerator)
    {
        $this->couponGenerator = $couponGenerator;
    }

    /**
     * Generate coupon list for specified cart price rule
     *
     * @param int|null $qty
     * @param int|null $ruleId
     * @param array $params
     *
     * @return string[]
     */
    public function execute(int $qty, int $ruleId, array $params = []): array
    {
        if (!$qty || !$ruleId) {
            return [];
        }

        $params['rule_id'] = $ruleId;
        $params['qty'] = $qty;

        return $this->couponGenerator->generateCodes($params);
    }
}
