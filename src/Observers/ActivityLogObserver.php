<?php

namespace Werk365\LaraKafka\Observers;

use Spatie\Activitylog\Models\Activity as ActivityLog;
use Werk365\LaraKafka\LaraKafka;

class ActivityLogObserver
{
    private $kafka;

    public function __construct()
    {
        $this->kafka = new LaraKafka();
    }

    /**
     * Handle the activity log "created" event.
     *
     * @param  \App\ActivityLog  $activityLog
     * @return void
     */
    public function created(ActivityLog $activityLog)
    {
        $this->kafka->setKey($activityLog->subject_type);
        $this->kafka->setBody(json_encode($activityLog));
        $this->kafka->produce();
    }

    /**
     * Handle the activity log "updated" event.
     *
     * @param  \App\ActivityLog  $activityLog
     * @return void
     */
    public function updated(ActivityLog $activityLog)
    {
        $this->kafka->setBody(json_encode($activityLog));
        $this->kafka->produce();
    }

    /**
     * Handle the activity log "deleted" event.
     *
     * @param  \App\ActivityLog  $activityLog
     * @return void
     */
    public function deleted(ActivityLog $activityLog)
    {
        $this->kafka->setBody(json_encode($activityLog));
        $this->kafka->produce();
    }

    /**
     * Handle the activity log "restored" event.
     *
     * @param  \App\ActivityLog  $activityLog
     * @return void
     */
    public function restored(ActivityLog $activityLog)
    {
        $this->kafka->setBody(json_encode($activityLog));
        $this->kafka->produce();
    }

    /**
     * Handle the activity log "force deleted" event.
     *
     * @param  \App\ActivityLog  $activityLog
     * @return void
     */
    public function forceDeleted(ActivityLog $activityLog)
    {
        $this->kafka->setBody(json_encode($activityLog));
        $this->kafka->produce();
    }
}
