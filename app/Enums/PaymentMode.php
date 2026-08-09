<?php

namespace App\Enums;

use Filament\Support\Colors\Color;

enum PaymentMode: string
{
    case Bank_Transfer = "Bank Transfer";
    case Cash = "Cash";
    case Cheque = "Cheque";
    case Credit_or_Debit_Card = "Credit or Debit Card";
    case Mobile_Money = "Mobile Money (M-PESA/AIRTEL)";
    case Online_Payment_Gateway = "Online Payment Gateway";
    case Credit_Loan = "Credited (Loan)";
    case From_Savings = "Savings Account";

    public function getLabel(): string
    {
        return match ($this) {
            self::Mobile_Money => 'M-PESA / Airtel Money',
            self::Credit_or_Debit_Card => 'Card',
            self::Online_Payment_Gateway => 'Online payment',
            self::Credit_Loan => 'Credited (loan)',
            self::From_Savings => "Member's savings",
            default => $this->value,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Cash => 'heroicon-o-banknotes',
            self::Bank_Transfer => 'heroicon-o-building-library',
            self::Cheque => 'heroicon-o-document-text',
            self::Credit_or_Debit_Card => 'heroicon-o-credit-card',
            self::Mobile_Money => 'heroicon-o-device-phone-mobile',
            self::Online_Payment_Gateway => 'heroicon-o-globe-alt',
            self::Credit_Loan => 'heroicon-o-arrow-right-end-on-rectangle',
            self::From_Savings => 'heroicon-o-wallet',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Cash => Color::Emerald,
            self::Mobile_Money => Color::Green,
            self::Bank_Transfer => Color::Blue,
            self::Cheque => Color::Indigo,
            self::Credit_or_Debit_Card => Color::Violet,
            self::Online_Payment_Gateway => Color::Sky,
            self::Credit_Loan => Color::Amber,
            self::From_Savings => Color::Orange,
        };
    }

    /**
     * The ways money can actually arrive from a member.
     *
     * "Savings account" and "Credited (loan)" are excluded because they are not
     * something the treasurer picks — the app sets them when money is moved
     * internally rather than paid in.
     *
     * @return array<self>
     */
    public static function externalModes(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => ! in_array($case, [self::From_Savings, self::Credit_Loan], true),
        ));
    }

    /**
     * @return array<string, string>
     */
    public static function externalOptions(): array
    {
        return collect(self::externalModes())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])
            ->all();
    }
}
