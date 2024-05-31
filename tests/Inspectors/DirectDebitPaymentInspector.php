<?php
declare(strict_types=1);

namespace WMDE\Fundraising\PaymentContext\Tests\Inspectors;

use WMDE\Fundraising\PaymentContext\Domain\Model\Iban;
use WMDE\Euro\Euro;
use WMDE\Fundraising\PaymentContext\Domain\Model\PaymentInterval;
use WMDE\Fundraising\PaymentContext\Domain\Model\DirectDebitPayment;

class DirectDebitPaymentInspector
{
    /** @var \ReflectionClass<DirectDebitPayment> */
    private \ReflectionClass $reflectedClass;

    public function __construct(private DirectDebitPayment $inspectionObject)
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

    public function getIban(): ?Iban
    {
        $value = $this->getPrivateValue('iban');
        assert($value === null || $value instanceof Iban);
        return $value;
    }

    public function getBic(): ?string
    {
        $value = $this->getPrivateValue('bic');
        assert($value === null || is_string($value));
        return $value;
    }

    public function getIsCancelled(): bool
    {
        $value = $this->getPrivateValue('isCancelled');
        assert(is_bool($value));
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
