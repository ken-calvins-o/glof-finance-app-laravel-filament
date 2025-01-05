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
    case Savings = "Savings";

}
