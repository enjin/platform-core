<?php

namespace Enjin\Platform\Services\Processor\Substrate\Events\Implementations\FuelTanks;

use Enjin\Platform\Exceptions\PlatformException;
use Enjin\Platform\Events\Substrate\FuelTanks\RuleSetInserted as RuleSetInsertedEvent;
use Enjin\Platform\Models\DispatchRule;
use Enjin\Platform\Models\Substrate\DispatchRulesParams;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\FuelTanks\RuleSetInserted as RuleSetInsertedPolkadart;
use Enjin\Platform\Services\Processor\Substrate\Codec\Polkadart\Events\Event;
use Enjin\Platform\Services\Processor\Substrate\Events\SubstrateEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class RuleSetInserted extends SubstrateEvent
{
    /** @var RuleSetInsertedPolkadart */
    protected Event $event;

    /**
     * Handle the rule set inserted event.
     *
     * @throws PlatformException
     */
    public function run(): void
    {
        $extrinsic = $this->block->extrinsics[$this->event->extrinsicIndex];
        $params = $extrinsic->params;
        $rules = $this->getValue($params, ['rule_set.rules', 'descriptor.rule_sets']);

        // Fail if it doesn't find the fuel tank
        $fuelTank = $this->getFuelTank($this->event->tankId);

        // Removes rules from that rule set id
        DispatchRule::where([
            'fuel_tank_id' => $fuelTank->id,
            'rule_set_id' => $this->event->ruleSetId,
        ])?->delete();

        $insertDispatchRules = empty(Arr::get($rules, '0.1.rules')) ? $this->parseRuleSetInsertedRuleSet($rules) : $this->parseTankCreatedRuleSets($rules);
        $fuelTank->dispatchRules()->createMany($insertDispatchRules);
    }

    public function log(): void
    {
        Log::debug(
            sprintf(
                'RuleSetInserted at FuelTank %s.',
                $this->event->tankId,
            )
        );
    }

    public function broadcast(): void
    {
        RuleSetInsertedEvent::safeBroadcast(
            $this->event,
            $this->getTransaction($this->block, $this->event->extrinsicIndex),
            $this->extra,
        );
    }

    protected function parseTankCreatedRuleSets(array $rules): array
    {
        $insertDispatchRules = [];

        foreach ($rules as $ruleSet) {
            $ruleSetId = $ruleSet[0];
            $rules = $ruleSet[1]['rules'];

            $dispatchRule = (new DispatchRulesParams())->fromEncodable($ruleSetId, ['rules' => $rules])->toArray();
            foreach ($dispatchRule as $rule) {
                $insertDispatchRules[] = [
                    'rule_set_id' => $ruleSetId,
                    'rule' => array_key_first($rule),
                    'value' => $rule[array_key_first($rule)],
                    'is_frozen' => false,
                ];
            }
        }

        return $insertDispatchRules;
    }

    protected function parseRuleSetInsertedRuleSet(array $rules): array
    {
        $insertDispatchRules = [];
        $dispatchRule = (new DispatchRulesParams())->fromEncodable($this->event->ruleSetId, ['rules' => $rules])->toArray();

        foreach ($dispatchRule as $rule) {
            $insertDispatchRules[] = [
                'rule_set_id' => $this->event->ruleSetId,
                'rule' => array_key_first($rule),
                'value' => $rule[array_key_first($rule)],
                'is_frozen' => false,
            ];
        }

        return $insertDispatchRules;
    }
}
