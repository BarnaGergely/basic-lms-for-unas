<?php
/**
 * Class Orders
 *
 * Represents and serializes from XML a collection of orders retrieved from the UNAS API.
 */

class Orders
{
    /** @var Order[] */
    public array $Order = [];

    public static function fromXml(string $xmlString): self
    {
        $xml = simplexml_load_string($xmlString);
        $orders = new self();
        if (isset($xml->Order)) {
            foreach ($xml->Order as $orderXml) {
                $orders->Order[] = Order::fromXml($orderXml);
            }
        }
        return $orders;
    }
}

class Order
{
    public ?string $Action = null;
    public ?string $Key = null;
    public ?string $Date = null;
    public ?string $DateMod = null;
    public ?string $Lang = null;
    public ?Customer $Customer = null;
    public ?string $Currency = null;
    public ?string $ExchangeValue = null;
    public ?string $Type = null;
    public ?string $Status = null;
    public ?string $StatusDetails = null;
    public ?string $StatusDateMod = null;
    public ?string $StatusEmail = null;
    public ?string $StatusID = null;
    public ?string $Authenticated = null;
    public ?Payment $Payment = null;
    public ?Shipping $Shipping = null;
    public ?Invoice $Invoice = null;
    public ?array $Params = null;
    public ?string $Referer = null;
    public ?string $Coupon = null;
    public ?float $Weight = null;
    public ?Info $Info = null;
    public ?Comments $Comments = null;
    public ?float $SumPriceGross = null;
    public ?Items $Items = null;

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $order = new self();
        $order->Action = (string)($xml->Action ?? null);
        $order->Key = (string)($xml->Key ?? null);
        $order->Date = (string)($xml->Date ?? null);
        $order->DateMod = (string)($xml->DateMod ?? null);
        $order->Lang = (string)($xml->Lang ?? null);
        $order->Customer = isset($xml->Customer) ? Customer::fromXml($xml->Customer) : null;
        $order->Currency = (string)($xml->Currency ?? null);
        $order->ExchangeValue = (string)($xml->ExchangeValue ?? null);
        $order->Type = (string)($xml->Type ?? null);
        $order->Status = (string)($xml->Status ?? null);
        $order->StatusDetails = (string)($xml->StatusDetails ?? null);
        $order->StatusDateMod = (string)($xml->StatusDateMod ?? null);
        $order->StatusEmail = (string)($xml->StatusEmail ?? null);
        $order->StatusID = (string)($xml->StatusID ?? null);
        $order->Authenticated = (string)($xml->Authenticated ?? null);
        $order->Payment = isset($xml->Payment) ? Payment::fromXml($xml->Payment) : null;
        $order->Shipping = isset($xml->Shipping) ? Shipping::fromXml($xml->Shipping) : null;
        $order->Invoice = isset($xml->Invoice) ? Invoice::fromXml($xml->Invoice) : null;
        $order->Params = isset($xml->Params) ? Params::fromXml($xml->Params) : null;
        $order->Referer = (string)($xml->Referer ?? null);
        $order->Coupon = (string)($xml->Coupon ?? null);
        $order->Weight = isset($xml->Weight) ? floatval($xml->Weight) : null;
        $order->Info = isset($xml->Info) ? Info::fromXml($xml->Info) : null;
        $order->Comments = isset($xml->Comments) ? Comments::fromXml($xml->Comments) : null;
        $order->SumPriceGross = isset($xml->SumPriceGross) ? floatval($xml->SumPriceGross) : null;
        $order->Items = isset($xml->Items) ? Items::fromXml($xml->Items) : null;
        return $order;
    }
}

class Customer
{
    public ?int $Id = null;
    public ?string $Email = null;
    public ?string $Username = null;
    public ?Contact $Contact = null;

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $customer = new self();
        $customer->Id = isset($xml->Id) ? intval($xml->Id) : null;
        $customer->Email = (string)($xml->Email ?? null);
        $customer->Username = (string)($xml->Username ?? null);
        $customer->Contact = isset($xml->Contact) ? Contact::fromXml($xml->Contact) : null;
        return $customer;
    }
}

class Contact
{
    public ?string $Name = null;
    public ?string $Phone = null;
    public ?string $Mobile = null;

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $contact = new self();
        $contact->Name = (string)($xml->Name ?? null);
        $contact->Phone = (string)($xml->Phone ?? null);
        $contact->Mobile = (string)($xml->Mobile ?? null);
        return $contact;
    }
}

class Payment
{
    public ?int $Id = null;
    public ?string $Name = null;
    public ?string $Type = null;
    public ?string $Status = null;
    public ?float $Paid = null;

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $payment = new self();
        $payment->Id = isset($xml->Id) ? intval($xml->Id) : null;
        $payment->Name = (string)($xml->Name ?? null);
        $payment->Type = (string)($xml->Type ?? null);
        $payment->Status = (string)($xml->Status ?? null);
        $payment->Paid = isset($xml->Paid) ? floatval($xml->Paid) : null;
        return $payment;
    }
}

class Shipping
{
    public ?int $Id = null;
    public ?string $Name = null;

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $shipping = new self();
        $shipping->Id = isset($xml->Id) ? intval($xml->Id) : null;
        $shipping->Name = (string)($xml->Name ?? null);
        return $shipping;
    }
}

class Invoice
{
    public ?int $Status = null;
    public ?string $StatusText = null;
    public ?string $Number = null;
    public ?string $Url = null;

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $invoice = new self();
        $invoice->Status = isset($xml->Status) ? intval($xml->Status) : null;
        $invoice->StatusText = (string)($xml->StatusText ?? null);
        $invoice->Number = (string)($xml->Number ?? null);
        $invoice->Url = (string)($xml->Url ?? null);
        return $invoice;
    }
}

class Params
{
    public array $Param = [];

    public static function fromXml(\SimpleXMLElement $xml): array
    {
        $params = [];
        if (isset($xml->Param)) {
            foreach ($xml->Param as $paramXml) {
                $params[] = [
                    'Id' => (string)($paramXml->Id ?? null),
                    'Name' => (string)($paramXml->Name ?? null),
                    'Value' => (string)($paramXml->Value ?? null),
                ];
            }
        }
        return $params;
    }
}

class Info
{
    public array $MergedFrom = [];
    public array $SeparatedTo = [];
    public array $SeparatedFrom = [];

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $info = new self();
        if (isset($xml->MergedFrom->Key)) {
            foreach ($xml->MergedFrom->Key as $key) {
                $info->MergedFrom[] = (string)$key;
            }
        }
        if (isset($xml->SeparatedTo->Key)) {
            foreach ($xml->SeparatedTo->Key as $key) {
                $info->SeparatedTo[] = (string)$key;
            }
        }
        if (isset($xml->SeparatedFrom->Key)) {
            foreach ($xml->SeparatedFrom->Key as $key) {
                $info->SeparatedFrom[] = (string)$key;
            }
        }
        return $info;
    }
}

class Comments
{
    public array $Comment = [];

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $comments = new self();
        if (isset($xml->Comment)) {
            foreach ($xml->Comment as $commentXml) {
                $comments->Comment[] = [
                    'Type' => (string)($commentXml->Type ?? null),
                    'Text' => (string)($commentXml->Text ?? null),
                ];
            }
        }
        return $comments;
    }
}

class Items
{
    /** @var Item[] */
    public array $Item = [];

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $items = new self();
        if (isset($xml->Item)) {
            foreach ($xml->Item as $itemXml) {
                $items->Item[] = Item::fromXml($itemXml);
            }
        }
        return $items;
    }
}

class Item
{
    public ?string $Id = null;
    public ?string $Sku = null;
    public ?string $Name = null;
    public ?float $Quantity = null;
    public ?float $PriceNet = null;
    public ?float $PriceGross = null;

    public static function fromXml(\SimpleXMLElement $xml): self
    {
        $item = new self();
        $item->Id = (string)($xml->Id ?? null);
        $item->Sku = (string)($xml->Sku ?? null);
        $item->Name = (string)($xml->Name ?? null);
        $item->Quantity = isset($xml->Quantity) ? floatval($xml->Quantity) : null;
        $item->PriceNet = isset($xml->PriceNet) ? floatval($xml->PriceNet) : null;
        $item->PriceGross = isset($xml->PriceGross) ? floatval($xml->PriceGross) : null;
        return $item;
    }
}
