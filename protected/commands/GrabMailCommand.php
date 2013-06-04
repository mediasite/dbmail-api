<?php

class GrabMailCommand extends GlobalConsoleCommand
{
    public $aggregateTasks = 30;
    public $longRunningTimeout = 7200; // 2h
    public $changeOwnerTimeout = 300; // 5m

    /**
     * @return string
     */
    public function getTaskPrefix()
    {
        return 'getmail';
    }

    /**
     * @param int[] $busyIds
     * @return int[]
     */
    public function getAvailableIds($busyIds)
    {
        $model = new GetMailRule;
        $model->dbCriteria->addNotInCondition('id', $busyIds);
        return EHtml::listData($model);
    }

    /**
     * @param int[] $ids
     * @return int pid of started process
     */
    protected function startProcess($ids)
    {
        $ruleModel = GetMailRule::model();
        $ruleModel->dbCriteria->addInCondition('id', $ids);

        /** @var GetMailRule[] $rules */
        $cmd = Yii::app()->params['getmail'];
        foreach ($ruleModel->findAll() as $rule) {
            /** @var GetMailRule $rule */
            $ruleFileName = $rule->getRuleFileName();
            $ruleDirectory = pathinfo($ruleFileName, PATHINFO_DIRNAME);
            if (!file_exists($ruleDirectory)) {
                mkdir($ruleDirectory, 0777, true);
            }
            file_put_contents($ruleFileName, $rule->getConfig());
            $cmd .= ' --rcfile ' . $ruleFileName;
        }

        $pid = exec($cmd . ' >/dev/null 2>&1 & echo $!');
        if (!$pid) {
            throw new CException('can\'t get pid of executed getmail process');
        }

        return $pid;
    }

    /**
     * @param int $id
     */
    public function postProcessing($id)
    {
        /** @var GetMailRule $rule */
        $rule = GetMailRule::model()->findByPk($id);

        $rule->status = $rule->getRuleStatus();
        unlink($rule->getLogFileName());
        $rule->save();
    }

}