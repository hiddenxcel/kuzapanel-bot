<?php

require_once __DIR__ . '/../lib/DB.php';

/**
 * Per-customer display currency for the WhatsApp bot conversation.
 *
 * TZS is the bot's canonical storage currency — every money-bearing DB
 * column (services.my_price, customers.balance, orders.amount, etc.) stays
 * TZS regardless of a customer's chosen currency. Conversion only happens at
 * display time (see WebhookController::money()); nothing is ever stored in
 * KES/UGX.
 *
 * exchange_rates.rate is "TZS per 1 unit of the foreign currency" (e.g. the
 * KES row's rate ~21.5 means 1 KES = 21.5 TZS) — the reverse of the more
 * common "units of foreign currency per 1 USD" convention, chosen because
 * TZS (not USD) is the base here, which keeps toBase() a single
 * multiplication with no reciprocal step.
 */
class CurrencyHelper
{
    public const BASE = 'TZS';
    public const SUPPORTED = ['TZS', 'KES', 'UGX'];

    /** @var array<string, float>|null per-request cache of the rates table */
    private static ?array $rateCache = null;

    public static function normalize(?string $currency): string
    {
        $currency = $currency !== null ? strtoupper($currency) : null;

        return in_array($currency, self::SUPPORTED, true) ? $currency : self::BASE;
    }

    /** The customer's saved display currency, defaulting to TZS. */
    public static function forCustomer(?array $customer): string
    {
        return self::normalize($customer['currency'] ?? null);
    }

    /**
     * Detect a country (and its bot language) from a WhatsApp phone number's
     * country code. Used once, at first contact, to auto-set a new
     * customer's currency + language — see Customer::getOrCreate().
     *
     * @return array{currency: string, lang: string}
     */
    public static function detectFromPhone(string $whatsappPhone): array
    {
        $digits = preg_replace('/\D/', '', $whatsappPhone);

        if (str_starts_with($digits, '254')) {
            return ['currency' => 'KES', 'lang' => 'en'];
        }

        if (str_starts_with($digits, '256')) {
            return ['currency' => 'UGX', 'lang' => 'en'];
        }

        // Tanzania (255) and anything unrecognized both fall back to the
        // bot's original default — no behavior change for existing markets.
        return ['currency' => 'TZS', 'lang' => 'sw'];
    }

    /**
     * TZS value of 1 unit of $currency. Falls back to 1.0 (no conversion)
     * if the currency has no enabled rate row — a missing/stale rate must
     * never block a reply, only make the shown amount momentarily
     * unconverted.
     */
    public static function rate(string $currency): float
    {
        if ($currency === self::BASE) {
            return 1.0;
        }

        if (self::$rateCache === null) {
            self::loadRates();
        }

        return self::$rateCache[$currency] ?? 1.0;
    }

    private static function loadRates(): void
    {
        self::$rateCache = [];

        try {
            $config = require __DIR__ . '/../../config/config.php';
            $stmt = DB::connect($config['db'])->query(
                'SELECT currency, rate FROM exchange_rates WHERE is_enabled = 1'
            );

            foreach ($stmt->fetchAll() as $row) {
                self::$rateCache[$row['currency']] = (float) $row['rate'];
            }
        } catch (\Throwable $e) {
            error_log('[CurrencyHelper] Failed to load exchange rates: ' . $e->getMessage());
        }
    }

    /** Bring a foreign-currency amount into the TZS base. */
    public static function toBase(float $amount, string $currency): float
    {
        $currency = self::normalize($currency);

        return $currency === self::BASE ? $amount : $amount * self::rate($currency);
    }

    /** Convert a TZS (base) amount into the customer's currency. */
    public static function convert(float $amountInTzs, string $currency): float
    {
        $currency = self::normalize($currency);

        return $currency === self::BASE ? $amountInTzs : $amountInTzs / self::rate($currency);
    }

    /**
     * The number to interpolate into a BotLang string's amount placeholder
     * — already converted, formatted with no decimals (matching the bot's
     * existing number_format($x, 0) convention throughout).
     */
    public static function format(float $amountInTzs, string $currency): string
    {
        return number_format(self::convert($amountInTzs, self::normalize($currency)), 0);
    }
}
