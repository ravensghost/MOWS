<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRecord extends Model
{
    protected $guarded = [];

    /**
     * Return all 20 service record event types grouped into their 4 logical optgroups.
     */
    public static function eventTypeGroups(): array
    {
        return [
            'career' => [
                'label' => lexicon('label_ServiceRecords_GroupCareer', 'Career & Duty'),
                'types' => [
                    'promotion'     => lexicon('label_ServiceRecords_TypePromotion', 'Promotion / Rank Change'),
                    'transfer'      => lexicon('label_ServiceRecords_TypeTransfer', 'Transfer / Reassignment'),
                    'award'         => lexicon('label_ServiceRecords_TypeAward', 'Award / Decoration'),
                    'status_change' => lexicon('label_ServiceRecords_TypeStatusChange', 'Duty Status Change'),
                    'assignment'    => lexicon('label_ServiceRecords_TypeAssignment', 'Mission Assignment'),
                    'commendation'  => lexicon('label_ServiceRecords_TypeCommendation', 'Official Commendation'),
                    'manual_entry'  => lexicon('label_ServiceRecords_TypeManualEntry', 'General / Manual Note'),
                ],
            ],
            'biographical' => [
                'label' => lexicon('label_ServiceRecords_GroupBiographical', 'Biographical History'),
                'types' => [
                    'background' => lexicon('label_ServiceRecords_TypeBackground', 'Early Background'),
                    'academy'    => lexicon('label_ServiceRecords_TypeAcademy', 'Academy / Cadet Record'),
                    'education'  => lexicon('label_ServiceRecords_TypeEducation', 'Education & Qualification'),
                    'training'   => lexicon('label_ServiceRecords_TypeTraining', 'Specialised Training'),
                    'milestone'  => lexicon('label_ServiceRecords_TypeMilestone', 'Major Life / Career Milestone'),
                    'incident'   => lexicon('label_ServiceRecords_TypeIncident', 'Notable Incident / Disciplinary'),
                ],
            ],
            'personal' => [
                'label' => lexicon('label_ServiceRecords_GroupPersonal', 'Personal & Family'),
                'types' => [
                    'family'       => lexicon('label_ServiceRecords_TypeFamily', 'Family Tie / Lineage'),
                    'relationship' => lexicon('label_ServiceRecords_TypeRelationship', 'Personal Relationship / Bond'),
                ],
            ],
            'medical_factional' => [
                'label' => lexicon('label_ServiceRecords_GroupMedicalFactional', 'Medical & Factional'),
                'types' => [
                    'psychological' => lexicon('label_ServiceRecords_TypePsychological', 'Psychological Profile'),
                    'evaluation'    => lexicon('label_ServiceRecords_TypeEvaluation', 'Command / Medical Evaluation'),
                    'physicals'     => lexicon('label_ServiceRecords_TypePhysicals', 'Physical / Medical Record'),
                    'affiliation'   => lexicon('label_ServiceRecords_TypeAffiliation', 'Group / Organisation Affiliation'),
                    'faction'       => lexicon('label_ServiceRecords_TypeFaction', 'Faction Standing / Allegiance'),
                ],
            ],
        ];
    }

    /**
     * Return a flat array of all 20 valid event_type keys.
     */
    public static function allEventTypes(): array
    {
        return [
            'promotion',
            'transfer',
            'award',
            'status_change',
            'manual_entry',
            'background',
            'academy',
            'education',
            'training',
            'assignment',
            'commendation',
            'milestone',
            'incident',
            'family',
            'relationship',
            'psychological',
            'evaluation',
            'physicals',
            'affiliation',
            'faction',
        ];
    }

    public function award()
    {
        return $this->belongsTo(Award::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
