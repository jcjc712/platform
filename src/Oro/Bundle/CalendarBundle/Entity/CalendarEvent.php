<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CalendarBundle\Model\ExtendCalendarEvent;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderData;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository")
 * @ORM\Table(
 *      name="oro_calendar_event",
 *      indexes={
 *          @ORM\Index(name="oro_calendar_event_idx", columns={"calendar_id", "start_at", "end_at"}),
 *          @ORM\Index(name="oro_calendar_event_updated_at_idx", columns={"updated_at"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_calendar_view_default",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-time"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "permissions"="VIEW;CREATE;EDIT;DELETE",
 *              "group_name"=""
 *          },
 *          "grouping"={
 *              "groups"={"activity"}
 *          },
 *          "reminder"={
 *              "reminder_template_name"="calendar_reminder",
 *              "reminder_flash_template_identifier"="calendar_event_template"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "route"="oro_calendar_event_activity_view",
 *              "acl"="oro_calendar_view",
 *              "action_button_widget"="oro_add_calendar_event_button",
 *              "action_link_widget"="oro_add_calendar_event_link"
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class CalendarEvent extends ExtendCalendarEvent implements RemindableInterface
{
    const NOT_RESPONDED        = 'not responded';
    const TENTATIVELY_ACCEPTED = 'tentatively accepted';
    const ACCEPTED             = 'accepted';
    const DECLINED             = 'declined';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CalendarEvent", mappedBy="parent", cascade={"remove"})
     **/
    protected $children;

    /**
     * @var CalendarEvent
     *
     * @ORM\ManyToOne(targetEntity="CalendarEvent", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     **/
    protected $parent;

    /**
     * @var Calendar
     *
     * @ORM\ManyToOne(targetEntity="Calendar", inversedBy="events")
     * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $calendar;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $end;

    /**
     * @var bool
     *
     * @ORM\Column(name="all_day", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $allDay;

    /**
     * @var Collection
     */
    protected $reminders;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="invitation_status", type="string", length=32)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $invitationStatus;

    public function __construct()
    {
        parent::__construct();

        $this->reminders = new ArrayCollection();
        $this->children  = new ArrayCollection();
    }

    /**
     * Gets an calendar event id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets owning calendar
     *
     * @return Calendar
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Sets owning calendar
     *
     * @param Calendar $calendar
     *
     * @return CalendarEvent
     */
    public function setCalendar(Calendar $calendar)
    {
        $this->calendar = $calendar;

        return $this;
    }

    /**
     * Gets calendar event title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets calendar event title.
     *
     * @param string $title
     *
     * @return CalendarEvent
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets calendar event description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets calendar event description.
     *
     * @param  string $description
     *
     * @return CalendarEvent
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets date/time an event begins.
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sets date/time an event begins.
     *
     * @param \DateTime $start
     *
     * @return CalendarEvent
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Gets date/time an event ends.
     *
     * If an event is all-day the end date is inclusive.
     * This means an event with start Nov 10 and end Nov 12 will span 3 days on the calendar.
     *
     * If an event is NOT all-day the end date is exclusive.
     * This is only a gotcha when your end has time 00:00. It means your event ends on midnight,
     * and it will not span through the next day.
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Sets date/time an event ends.
     *
     * @param \DateTime $end
     *
     * @return CalendarEvent
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Indicates whether an event occurs at a specific time-of-day.
     *
     * @return bool
     */
    public function getAllDay()
    {
        return $this->allDay;
    }

    /**
     * Sets a flag indicates whether an event occurs at a specific time-of-day.
     *
     * @param bool $allDay
     *
     * @return CalendarEvent
     */
    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getReminders()
    {
        return $this->reminders;
    }

    /**
     * {@inheritdoc}
     */
    public function setReminders(Collection $reminders)
    {
        $this->reminders = $reminders;
    }

    /**
     * {@inheritdoc}
     */
    public function getReminderData()
    {
        $result = new ReminderData();

        $result->setSubject($this->getTitle());
        $result->setExpireAt($this->getStart());
        $result->setRecipient($this->getCalendar()->getOwner());

        return $result;
    }

    /**
     * Get children calendar events
     *
     * @return Collection|CalendarEvent[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set children calendar events.
     *
     * @param Collection|CalendarEvent[] $calendarEvents
     *
     * @return CalendarEvent
     */
    public function resetChildren($calendarEvents)
    {
        $this->children->clear();

        foreach ($calendarEvents as $calendarEvent) {
            $this->addChildCalendarEvent($calendarEvent);
        }

        return $this;
    }

    /**
     * Add child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function addChildCalendarEvent(CalendarEvent $calendarEvent)
    {
        if (!$this->children->contains($calendarEvent)) {
            $this->children->add($calendarEvent);
            $calendarEvent->setParent($this);
        }

        return $this;
    }

    /**
     * Remove child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function removeChildCalendarEvent(CalendarEvent $calendarEvent)
    {
        if ($this->children->contains($calendarEvent)) {
            $this->children->removeElement($calendarEvent);
        }

        return $this;
    }

    /**
     * Set parent calendar event.
     *
     * @param CalendarEvent $parent
     */
    public function setParent(CalendarEvent $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent calendar event.
     *
     * @return CalendarEvent
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return string
     */
    public function getInvitationStatus()
    {
        return $this->invitationStatus;
    }

    /**
     * @param string $invitationStatus
     */
    public function setInvitationStatus($invitationStatus)
    {
        $this->invitationStatus = $invitationStatus;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = clone $this->createdAt;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }
}
