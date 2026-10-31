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
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A timer started from the "Start" form begins when it is saved, to the second.
 *
 * The form types the begin as "HH:mm" and TimesheetEditForm keeps the seconds only for existing entries: new ones
 * always begin at :00. Kimai prefills a new entry with the current time, at every request: when the form is saved,
 * "prefilled" is the time of the save. Two cases take it as the begin:
 *
 * - the page's script (TimerSecondsSubscriber) sent UNTOUCHED: the begin shown when the form opened was left as it
 *   was, so the timer starts now, even if the minute changed while the form was open;
 * - without the script, the submitted begin is still in the minute of the save.
 *
 * Other begins (typed by hand) and entries with an end or a duration keep what was entered.
 */
final class StartSecondsExtension extends AbstractTypeExtension
{
    /** POST field set by the page's script when the user did not change the prefilled begin. */
    public const UNTOUCHED = 'timer_seconds_begin_untouched';

    public function __construct(private readonly RequestStack $requestStack)
    {
    }

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

        // priority -10: after the SUBMIT listeners of TimesheetEditForm, which build the begin with 0 seconds and set
        // the end (typed, or begin + duration)
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use (&$prefilled): void {
            $timesheet = $event->getData();
            if ($prefilled === null || !$timesheet instanceof Timesheet || $timesheet->getId() !== null) {
                return;
            }
            $begin = $timesheet->getBegin();
            if ($begin === null) {
                return;
            }
            // "prefilled" is now only for the create form (a duplicated entry keeps the begin it copies)
            $untouched = $timesheet->getEnd() === null
                && abs(time() - $prefilled->getTimestamp()) < 120
                && $this->requestStack->getCurrentRequest()?->request->has(self::UNTOUCHED);
            if (!$untouched && intdiv($begin->getTimestamp(), 60) !== intdiv($prefilled->getTimestamp(), 60)) {
                return;
            }
            $newBegin = clone $begin;
            $newBegin->setTimestamp($prefilled->getTimestamp());
            $timesheet->setBegin($newBegin);
        }, -10);
    }
}
