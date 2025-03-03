<?php

namespace Enjin\Platform\Models\Substrate;

use Illuminate\Support\Str;

class DispatchRulesParams
{
    /**
     * Create a new dispatch rule params instance.
     */
    public function __construct(
        public ?int $ruleSetId = 0,
        public ?WhitelistedCallersParams $whitelistedCallers = null,
        public ?RequireTokenParams $requireToken = null,
        public ?WhitelistedCollectionsParams $whitelistedCollections = null,
        public ?MaxFuelBurnPerTransactionParams $maxFuelBurnPerTransaction = null,
        public ?UserFuelBudgetParams $userFuelBudget = null,
        public ?TankFuelBudgetParams $tankFuelBudget = null,
        public ?PermittedCallsParams $permittedCalls = null,
        public ?PermittedExtrinsicsParams $permittedExtrinsics = null,
        public ?WhitelistedPalletsParams $whitelistedPallets = null,
        public ?RequireSignatureParams $requireSignature = null,
        public ?MinimumInfusionParams $minimumInfusion = null,
        public ?bool $isFrozen = false,
    ) {}

    /**
     * Create a new instance from the given parameters.
     */
    public function fromEncodable(int $setId, mixed $params): self
    {
        $this->ruleSetId = $setId;
        $this->isFrozen = $params['isFrozen'] ?? false;

        foreach ($params['rules'] as $rule) {
            $ruleParam = '\Enjin\Platform\Models\Substrate\\' . ($ruleName = array_key_first($rule)) . 'Params';
            $ruleParams = $ruleParam::fromEncodable($rule);
            $this->{Str::camel($ruleName)} = $ruleParams;
        }

        return $this;
    }

    /**
     * Returns the encodable representation of this instance.
     */
    public function toEncodable(): array
    {
        $params = [];

        if ($this->whitelistedCallers) {
            $params[] = $this->whitelistedCallers->toEncodable();
        }

        if ($this->requireToken) {
            $params[] = $this->requireToken->toEncodable();
        }

        if ($this->whitelistedCollections) {
            $params[] = $this->whitelistedCollections->toEncodable();
        }

        if ($this->maxFuelBurnPerTransaction) {
            $params[] = $this->maxFuelBurnPerTransaction->toEncodable();
        }

        if ($this->userFuelBudget) {
            $params[] = $this->userFuelBudget->toEncodable();
        }

        if ($this->tankFuelBudget) {
            $params[] = $this->tankFuelBudget->toEncodable();
        }

        if ($this->whitelistedPallets) {
            $params[] = $this->whitelistedPallets->toEncodable();
        }

        if ($this->requireSignature) {
            $params[] = $this->requireSignature->toEncodable();
        }

        // This is encoded manually as we can't encode it using php-scale-codec lib
        if ($this->permittedExtrinsics) {
            $params[] = ['PermittedExtrinsics' => ['extrinsics' => []]];
        }

        if ($this->minimumInfusion) {
            $params[] = $this->minimumInfusion->toEncodable();
        }


        return $params;
    }

    public function toArray(): array
    {
        $params = [];

        if ($this->whitelistedCallers) {
            $params[] = $this->whitelistedCallers->toArray();
        }

        if ($this->requireToken) {
            $params[] = $this->requireToken->toArray();
        }

        if ($this->whitelistedCollections) {
            $params[] = $this->whitelistedCollections->toArray();
        }

        if ($this->maxFuelBurnPerTransaction) {
            $params[] = $this->maxFuelBurnPerTransaction->toArray();
        }

        if ($this->userFuelBudget) {
            $params[] = $this->userFuelBudget->toArray();
        }

        if ($this->tankFuelBudget) {
            $params[] = $this->tankFuelBudget->toArray();
        }

        if ($this->whitelistedPallets) {
            $params[] = $this->whitelistedPallets->toArray();
        }

        if ($this->permittedCalls) {
            $params[] = $this->permittedCalls->toArray();
        }

        if ($this->permittedExtrinsics) {
            $params[] = $this->permittedExtrinsics->toArray();
        }

        if ($this->requireSignature) {
            $params[] = $this->requireSignature->toArray();
        }

        if ($this->minimumInfusion) {
            $params[] = $this->minimumInfusion->toArray();
        }

        return $params;
    }
}
