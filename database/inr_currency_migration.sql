-- FireZone Store: INR Currency & Pricing Migration
-- Sets Indian Rupee (INR / ₹) as the sole global currency across all database tables

USE freefire_store;

-- 1. Global Settings
UPDATE settings SET setting_value = '₹' WHERE setting_key = 'currency_symbol';
UPDATE settings SET setting_value = 'INR' WHERE setting_key = 'currency_code';
UPDATE settings SET setting_value = '25.00' WHERE setting_key = 'referral_reward_amount';
UPDATE settings SET setting_value = '100.00' WHERE setting_key = 'referral_min_order';
UPDATE settings SET setting_value = 'Transfer INR amount using Instant UPI / Bank Transfer:\n• UPI ID: firezonepay@upi (GPay / PhonePe / Paytm)\n• IMPS Bank: HDFC Bank / ACC 5020008492019 / IFSC HDFC0001234\n• Reference Note: Enter your Store Username\n• Upload/enter the 12-digit UPI / UTR Reference ID below for instant admin verification.' WHERE setting_key = 'deposit_instructions';

-- 2. Payment Gateways: Set all gateways to INR
UPDATE payment_gateways SET currency = 'INR';
ALTER TABLE payment_gateways ALTER COLUMN currency SET DEFAULT 'INR';

-- 3. Realistic Free Fire Diamond Top-Up INR Pricing
UPDATE products SET price = 80.00, original_price = 100.00, flash_sale_price = 69.00 WHERE id = 1;
UPDATE products SET price = 240.00, original_price = 290.00 WHERE id = 2;
UPDATE products SET price = 400.00, original_price = 480.00 WHERE id = 3;
UPDATE products SET price = 800.00, original_price = 960.00 WHERE id = 4;
UPDATE products SET price = 1600.00, original_price = 1900.00 WHERE id = 5;
UPDATE products SET price = 4000.00, original_price = 4800.00 WHERE id = 6;
UPDATE products SET price = 160.00, original_price = 200.00 WHERE id = 7;
UPDATE products SET price = 640.00, original_price = 800.00 WHERE id = 8;
UPDATE products SET price = 280.00, original_price = 350.00 WHERE id = 9;
UPDATE products SET price = 200.00, original_price = 250.00 WHERE id = 10;
UPDATE products SET price = 200.00, original_price = 250.00 WHERE id = 11;

-- 4. Product Variants INR Pricing
UPDATE product_variants SET price = 80.00, original_price = 100.00 WHERE id = 1;
UPDATE product_variants SET price = 240.00, original_price = 290.00 WHERE id = 2;
UPDATE product_variants SET price = 400.00, original_price = 480.00 WHERE id = 3;
UPDATE product_variants SET price = 800.00, original_price = 960.00 WHERE id = 4;
UPDATE product_variants SET price = 1600.00, original_price = 1900.00 WHERE id = 5;
UPDATE product_variants SET price = 155.00, original_price = 195.00 WHERE id = 8;
UPDATE product_variants SET price = 465.00, original_price = 560.00 WHERE id = 9;
UPDATE product_variants SET price = 780.00, original_price = 940.00 WHERE id = 10;
UPDATE product_variants SET price = 1550.00, original_price = 1860.00 WHERE id = 11;
UPDATE product_variants SET price = 3100.00, original_price = 3700.00 WHERE id = 12;

-- 5. Coupons in INR
UPDATE coupons SET discount_value = 10.00, min_order_amount = 150.00, max_discount = 100.00 WHERE id = 1;
UPDATE coupons SET discount_value = 50.00, min_order_amount = 200.00, max_discount = 50.00 WHERE id = 2;
UPDATE coupons SET discount_value = 20.00, min_order_amount = 500.00, max_discount = 250.00 WHERE id = 3;

-- 6. User Wallet Balances in INR
UPDATE users SET wallet_balance = 500.00 WHERE wallet_balance < 100.00;
