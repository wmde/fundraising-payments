<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Tests\Inspectors;

use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentReferenceCode;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\BankTransferPayment;

class BankTransferPaymentInspector
{
    /** @var \ReflectionClass<BankTransferPayment> */
    private \ReflectionClass $reflectedClass;

    public function __construct(private BankTransferPayment $inspectionObject)
    {
        $this->reflectedClass = new \ReflectionClass($inspectionObject);
    }

    private function getPrivateValue(string $propertyName): mixed
    {
        if (!$this->reflectedClass->hasProperty($propertyName)) {
            throw new \LogicException(sprintf(
                "Property %s not found in class %s. Try re-generating the class %s",
                $propertyName, $this->reflectedClass->getName(), self::class
            ));
        }
        $prop = $this->reflectedClass->getProperty($propertyName);
        $prop->setAccessible(true);
        return $prop->getValue($this->inspectionObject);
    }

    public function getPaymentReferenceCode(): ?PaymentReferenceCode
    {
        $value = $this->getPrivateValue('paymentReferenceCode');
        assert($value === null || $value instanceof PaymentReferenceCode);
        return $value;
    }

    public function getId(): int
    {
        $value = $this->getPrivateValue('id');
        assert(is_int($value));
        return $value;
    }

    public function getAmount(): Euro
    {
        $value = $this->getPrivateValue('amount');
        assert($value instanceof Euro);
        return $value;
    }

    public function getInterval(): PaymentInterval
    {
        $value = $this->getPrivateValue('interval');
        assert($value instanceof PaymentInterval);
        return $value;
    }

    public function getPaymentMethod(): string
    {
        $value = $this->getPrivateValue('paymentMethod');
        assert(is_string($value));
        return $value;
    }
}
