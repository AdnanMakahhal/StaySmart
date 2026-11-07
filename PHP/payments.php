<?php
function staySmartPaymentMethods(): array
{
    return ['pay_at_property'=>'Pay in the lobby', 'pay_at_checkout'=>'Pay at hotel checkout', 'apple_pay_demo'=>'Apple Pay (demo)', 'samsung_pay_demo'=>'Samsung Pay (demo)', 'visa_demo'=>'Visa credit / debit (demo)', 'mastercard_demo'=>'Mastercard credit / debit (demo)'];
}
function staySmartEnsurePayments(mysqli $db): void
{
    $db->query("CREATE TABLE IF NOT EXISTS booking_payments (
        booking_id INT UNSIGNED NOT NULL PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        method ENUM('pay_at_property') NOT NULL DEFAULT 'pay_at_property',
        status ENUM('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid',
        amount DECIMAL(12,2) NOT NULL,
        currency CHAR(3) NOT NULL DEFAULT 'USD',
        created_at DATETIME NOT NULL,
        KEY idx_booking_payments_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $column = $db->query("SHOW COLUMNS FROM booking_payments LIKE 'method'")->fetch_assoc();
    if (strpos($column['Type'], 'apple_pay_demo') === false) {
        $db->query("ALTER TABLE booking_payments MODIFY method ENUM('pay_at_property','pay_at_checkout','apple_pay_demo','samsung_pay_demo','visa_demo','mastercard_demo') NOT NULL DEFAULT 'pay_at_property'");
    }
}
