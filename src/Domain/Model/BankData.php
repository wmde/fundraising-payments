<?php

declare( strict_types = 1 );

namespace WMDE\Fundraising\PaymentContext\Domain\Model;

use WMDE\FreezableValueObject\FreezableValueObject;

/**
 * @license GPL-2.0-or-later
 * @author Kai Nissen < kai.nissen@wikimedia.de >
 * @author Christoph Fischer < christoph.fischer@wikimedia.de >
 */
class BankData {
	use FreezableValueObject;

	private string $bic = '';
	private Iban $iban;
	private string $account = '';
	private string $bankCode = '';
	private string $bankName = '';

	public function getBic(): string {
		return $this->bic;
	}

	public function setBic( string $bic ): self {
		$this->assertIsWritable();
		$this->bic = $bic;
		return $this;
	}

	public function getIban(): Iban {
		return $this->iban;
	}

	public function setIban( Iban $iban ): self {
		$this->assertIsWritable();
		$this->iban = $iban;
		return $this;
	}

	public function getAccount(): string {
		return $this->account;
	}

	public function setAccount( string $account ): self {
		$this->assertIsWritable();
		$this->account = $account;
		return $this;
	}

	public function getBankCode(): string {
		return $this->bankCode;
	}

	public function setBankCode( string $bankCode ): self {
		$this->assertIsWritable();
		$this->bankCode = $bankCode;
		return $this;
	}

	public function getBankName(): string {
		return $this->bankName;
	}

	public function setBankName( string $bankName ): self {
		$this->assertIsWritable();
		$this->bankName = $bankName;
		return $this;
	}

	public function hasIban(): bool {
		return $this->getIban()->toString() !== '';
	}

	public function hasBic(): bool {
		return $this->getBic() !== '';
	}

	public function hasCompleteLegacyBankData(): bool {
		return $this->getAccount() !== '' && $this->getBankCode() !== '';
	}

	public function isComplete(): bool {
		return $this->hasIban() && $this->hasBic();
	}

}
