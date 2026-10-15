<?php

namespace KimaiPlugin\TimerSecondsBundle\Form;

use App\Entity\Timesheet;
use App\Form\TimesheetAdminEditForm;
use App\Form\TimesheetEditForm;
use App\Form\TimesheetMultiUserEditForm;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Keeps the seconds of a new entry that begins in the current minute (e.g. a timer started from the "Start" form).
 *
 * The form types the begin as "HH:mm" and TimesheetEditForm keeps the seconds only for existing entries: new ones
 * always begin at :00. Kimai prefills a new entry with the current time, so when the submitted begin is still in
 * the prefilled minute, this restores the prefilled seconds. Other begins (typed by hand) keep :00.
 */
final class StartSecondsExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        // the admin forms extend TimesheetEditForm as PHP classes, not as form parents
        return [TimesheetEditForm::class, TimesheetAdminEditForm::class, TimesheetMultiUserEditForm::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!$builder->has('begin_time')) {
            return;
        }

        $prefilled = null;
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use (&$prefilled): void {
            $timesheet = $event->getData();
            $prefilled = null;
            if ($timesheet instanceof Timesheet && $timesheet->getId() === null && $timesheet->getBegin() !== null) {
                $prefilled = clone $timesheet->getBegin();
            }
        });

        // priority -10: after the SUBMIT listener of TimesheetEditForm, which builds the begin with 0 seconds
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use (&$prefilled): void {
            $timesheet = $event->getData();
            if ($prefilled === null || !$timesheet instanceof Timesheet || $timesheet->getId() !== null) {
                return;
            }
            $begin = $timesheet->getBegin();
            if ($begin === null || intdiv($begin->getTimestamp(), 60) !== intdiv($prefilled->getTimestamp(), 60)) {
                return;
            }
            $newBegin = clone $begin;
            $newBegin->setTimestamp($prefilled->getTimestamp());
            $timesheet->setBegin($newBegin);
        }, -10);
    }
}
