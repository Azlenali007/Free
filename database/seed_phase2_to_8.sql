-- Seed initial data for Phase 2 to Phase 8

-- 1. Payment Gateways
INSERT INTO `payment_gateways` (`code`, `name`, `title`, `instructions`, `currency`, `fee_percent`, `credentials`, `status`, `sort_order`) VALUES
('wallet', 'Store Wallet', 'Pay using FireZone Wallet Balance', 'Instant 1-click checkout using your funded store balance.', 'INR', 0.00, '{"type":"internal"}', 'active', 1),
('manual_deposit', 'Manual Bank / UPI / Crypto', 'Direct Bank Transfer / UPI / USDT', 'Send payment to our verified gaming account and provide Transaction Reference ID for swift admin processing.', 'INR', 0.00, '{"account_name":"FireZone Official","upi_id":"firezone@okaxis","usdt_trc20":"TN8x...92Z"}', 'active', 2),
('stripe', 'Credit / Debit Card (Stripe)', 'Stripe Card Gateway', 'Pay securely using any International Visa, MasterCard, or Amex card.', 'INR', 2.50, '{"publishable_key":"pk_live_fz9823...","secret_key":"sk_live_fz8712..."}', 'active', 3),
('razorpay', 'Razorpay UPI & NetBanking', 'Instant UPI / QR Code / NetBanking', 'Seamless payments via Google Pay, PhonePe, Paytm and NetBanking.', 'INR', 1.50, '{"key_id":"rzp_live_9812...","key_secret":"sec_8712..."}', 'active', 4),
('mobile_wallet', 'bKash / Nagad / EasyPaisa', 'South Asia Mobile Financial Services', 'Instant mobile top-up verification via Personal/Merchant number.', 'INR', 0.00, '{"bkash":"+8801700000000","nagad":"+8801800000000"}', 'active', 5)
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `currency` = VALUES(`currency`);

-- 2. Banners for Homepage Slider
INSERT INTO `banners` (`title`, `subtitle`, `description`, `badge`, `image`, `button_text`, `button_url`, `sort_order`, `status`) VALUES
('SUMMER EVO DIAMOND BLITZ', 'Instant Direct Free Fire UID Top-Up', 'Equip your favorite Evo weapons and unlock exclusive legendary skins in under 3 minutes with 100% account safety guarantee.', 'SPECIAL OFFER', '', 'Explore Diamonds', '/products.php', 1, 'active'),
('WEEKLY & MONTHLY PASSES', 'Save Over 45% on Free Fire Subscriptions', 'Get daily diamonds and VIP badge bonuses credited straight to your Player ID every single morning.', 'BEST VALUE', '', 'Get Membership', '/products.php?cat=memberships-passes', 2, 'active'),
('BECOME A CERTIFIED RESELLER', 'Exclusive Wholesale Diamond Pricing & API Access', 'High-volume diamond top-up discounts and direct automated API endpoints for gaming shops and esports teams.', 'B2B PARTNERSHIP', '', 'Reseller Panel', '/reseller.php', 3, 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 3. Coupons
INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_order_amount`, `max_discount`, `start_date`, `expiry_date`, `usage_limit`, `per_user_limit`, `status`) VALUES
('FIRE10', 'percentage', 10.00, 100.00, 150.00, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 1000, 3, 'active'),
('WELCOME50', 'fixed', 50.00, 199.00, 50.00, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 500, 1, 'active'),
('DIAMOND20', 'percentage', 20.00, 499.00, 300.00, '2026-01-01 00:00:00', '2027-12-31 23:59:59', 200, 2, 'active')
ON DUPLICATE KEY UPDATE `discount_value` = VALUES(`discount_value`);

-- 4. Featured Products & Flash Sale setup on existing products
UPDATE `products` SET `is_featured` = 1 WHERE `id` IN (1, 2, 3, 5);

-- Set an active flash sale on product 1 (e.g. 100+10 diamonds or first product)
UPDATE `products` 
SET `flash_sale_enabled` = 1,
    `flash_sale_price` = ROUND(`price` * 0.80, 2),
    `flash_sale_start` = NOW() - INTERVAL 1 HOUR,
    `flash_sale_end` = NOW() + INTERVAL 48 HOUR
WHERE `id` = 1;

-- 5. Product Variants for products
INSERT INTO `product_variants` (`product_id`, `name`, `price`, `original_price`, `diamonds_amount`, `bonus_diamonds`, `description`, `status`, `sort_order`) 
SELECT id, 'Single Delivery', price, original_price, diamonds_amount, bonus_diamonds, 'Standard direct UID top-up pack', 'active', 1
FROM `products` WHERE id <= 5
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `product_variants` (`product_id`, `name`, `price`, `original_price`, `diamonds_amount`, `bonus_diamonds`, `description`, `status`, `sort_order`)
SELECT id, 'Double Diamond Pack (2x)', ROUND(price * 1.90, 2), ROUND(price * 2.00, 2), diamonds_amount * 2, bonus_diamonds * 2 + 15, 'Best value 2x diamond pack delivered directly', 'active', 2
FROM `products` WHERE id <= 5
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 6. Providers
INSERT INTO `providers` (`name`, `api_url`, `api_key`, `api_secret`, `balance`, `status`, `notes`) VALUES
('Smile.one Global Direct API', 'https://api.smile.one/v1/topup', 'sm_live_938172901823', 'sec_smile_ff_direct_key', 250.00, 'active', 'Primary direct Garena Free Fire authorized UID top-up provider'),
('Garena Partner Gateway', 'https://partner.garena.com/api/v2/orders', 'gar_partner_token_9812', 'sec_gar_partner_99182', 150.00, 'inactive', 'Secondary reserve provider API for peak flash sales')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 7. Add referral codes for existing users
UPDATE `users` SET `referral_code` = CONCAT('FZ', id, UPPER(SUBSTRING(MD5(id), 1, 4))) WHERE `referral_code` IS NULL OR `referral_code` = '';

-- 8. Additional Settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('referral_enabled', '1', 'marketing', 'Enable user referral rewards'),
('referral_reward_amount', '0.25', 'marketing', 'Reward credited to referrer wallet per qualifying purchase'),
('referral_min_order', '1.00', 'marketing', 'Minimum order total to qualify for referral reward'),
('reseller_system_enabled', '1', 'reseller', 'Enable reseller portal and custom wholesale pricing'),
('reseller_default_discount', '10', 'reseller', 'Default reseller discount percent'),
('admin_2fa_enabled', '0', 'security', 'Global require 2FA for admin accounts'),
('max_login_attempts', '5', 'security', 'Max failed login attempts before temporary lockout'),
('lockout_duration_minutes', '15', 'security', 'Duration of temporary account lock in minutes')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
