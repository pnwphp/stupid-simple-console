<?php

namespace PNWPHP\SSC\Service;

class AutoScalingService
{
    private $asg;

    public function __construct(\Aws\Sdk $sdk)
    {
        $this->asg = $sdk->createAutoScaling();
    }

    public function getStatusList(array $grouplist)
    {
        $arnsWanted = array_map(function($g) {
            return $g['arn'];
        }, $grouplist);

        $data = $this->asg->describeAutoScalingGroups();

        $groups = $data['AutoScalingGroups'];

        // TODO: filter based on input
        $processed = array_map(function($g){return $this->processGroup($g);}, $groups);

        return array_filter($processed, function($g) use ($arnsWanted) {
            return in_array($g['arn'], $arnsWanted);
        });
    }

    private function processGroup($group)
    {
        $instances = $group['Instances'];
        $healthyInstances = array_filter($instances, function($i) {
            return
                $i['HealthStatus'] === 'Healthy' &&
                $i['LifecycleState'] === 'InService';
        });

        return [
            'name' => 'someFakeName',
            'type' => 'group',
            'arn' => $group['AutoScalingGroupARN'],
            'status' => count($healthyInstances) > 0,
            'count' => count($healthyInstances),
        ];
    }
}
