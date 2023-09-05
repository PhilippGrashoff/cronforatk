<?php declare(strict_types=1);

namespace cronforatk\docs;

use cronforatk\BaseCronJob;

class MyCronJob extends BaseCronJob
{

    public static string $name = 'SomeNameForThisCron';
    public static string $description = 'SomeDescriptionExplainingWhatThisIsDoing';

    public bool $strict = false;

    public function execute(): void
    {
        //do something
        $someModel = new SomeModel($this->persistence);
        $someModel->setLimit(100);
        foreach ($someModel as $entity) {
            if($this->strict) {
                $entity->doSomeStrictCheck();
            }
            else {
                $entity->doSomeOtherCheck();
            }
            //optionally add some output to log
            $this->executionLog[] = 'SomeModel With ID ' . $entity->getId()
                . 'checked at ' . (new \DateTime())->format(DATE_ATOM);
        }
    }
}
