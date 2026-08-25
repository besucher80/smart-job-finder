<?php

declare(strict_types=1);

defined('TYPO3') or die();

$lll = 'LLL:EXT:smart_job_finder/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $lll . 'tx_smartjobfinder_domain_model_job',
        'label' => 'title',
        'label_alt' => 'location, employment_type',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,teaser,description,location,department',
        'iconfile' => 'EXT:smart_job_finder/Resources/Public/Icons/Job.svg',
        'typeicon_classes' => [
            'default' => 'smart-job-finder-job',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    featured, title, slug, department, teaser, description,
                --div--;' . $lll . 'tx_smartjobfinder_domain_model_job.tab.meta,
                    company, location, location_country,
                    --palette--;;contract,
                --div--;' . $lll . 'tx_smartjobfinder_domain_model_job.tab.salary,
                    --palette--;;salary,
                    valid_through, application_url, contact_email,
                --div--;' . $lll . 'tx_smartjobfinder_domain_model_job.tab.details,
                    requirements, benefits,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
            ',
        ],
    ],
    'palettes' => [
        'contract' => [
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.palette.contract',
            'showitem' => 'employment_type, workplace_type',
        ],
        'salary' => [
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.palette.salary',
            'showitem' => 'salary_min, salary_max, salary_currency, salary_interval',
        ],
        'language' => [
            'showitem' => 'sys_language_uid, l10n_parent',
        ],
        'hidden' => [
            'showitem' => 'hidden',
        ],
        'access' => [
            'showitem' => 'starttime, endtime',
        ],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_smartjobfinder_domain_model_job',
                'foreign_table_where' => 'AND {#tx_smartjobfinder_domain_model_job}.{#pid}=###CURRENT_PID### AND {#tx_smartjobfinder_domain_model_job}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'exclude' => false,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.title',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.title.description',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'slug' => [
            'exclude' => false,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.slug',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.slug.description',
            'config' => [
                'type' => 'slug',
                'size' => 50,
                'generatorOptions' => [
                    'fields' => ['title'],
                    'fieldSeparator' => '-',
                    'prefixParentPageSlug' => false,
                    'replacements' => [
                        '/' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
                'default' => '',
            ],
        ],
        'featured' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.featured',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.featured.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'teaser' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.teaser',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3,
                'eval' => 'trim',
            ],
        ],
        'description' => [
            'exclude' => false,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'cols' => 40,
                'rows' => 15,
            ],
        ],
        'department' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.department',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'location' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.location',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.location.description',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
                'eval' => 'trim',
            ],
        ],
        'location_country' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.location_country',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 2,
                'eval' => 'trim,alpha,nospace,upper',
                'default' => 'DE',
            ],
        ],
        'employment_type' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.employment_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => $lll . 'employment_type.FULL_TIME', 'value' => 'FULL_TIME'],
                    ['label' => $lll . 'employment_type.PART_TIME', 'value' => 'PART_TIME'],
                    ['label' => $lll . 'employment_type.CONTRACTOR', 'value' => 'CONTRACTOR'],
                    ['label' => $lll . 'employment_type.TEMPORARY', 'value' => 'TEMPORARY'],
                    ['label' => $lll . 'employment_type.INTERN', 'value' => 'INTERN'],
                ],
                'default' => 'FULL_TIME',
            ],
        ],
        'workplace_type' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.workplace_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => $lll . 'workplace_type.ONSITE', 'value' => 'ONSITE'],
                    ['label' => $lll . 'workplace_type.HYBRID', 'value' => 'HYBRID'],
                    ['label' => $lll . 'workplace_type.REMOTE', 'value' => 'REMOTE'],
                ],
                'default' => 'ONSITE',
            ],
        ],
        'salary_min' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.salary_min',
            'config' => [
                'type' => 'number',
                'format' => 'integer',
                'default' => 0,
                'range' => [
                    'lower' => 0,
                ],
            ],
        ],
        'salary_max' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.salary_max',
            'config' => [
                'type' => 'number',
                'format' => 'integer',
                'default' => 0,
                'range' => [
                    'lower' => 0,
                ],
            ],
        ],
        'salary_currency' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.salary_currency',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'max' => 3,
                'eval' => 'trim,alpha,nospace,upper',
                'default' => 'EUR',
            ],
        ],
        'salary_interval' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.salary_interval',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => $lll . 'salary_interval.YEAR', 'value' => 'YEAR'],
                    ['label' => $lll . 'salary_interval.MONTH', 'value' => 'MONTH'],
                    ['label' => $lll . 'salary_interval.HOUR', 'value' => 'HOUR'],
                ],
                'default' => 'YEAR',
            ],
        ],
        'valid_through' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.valid_through',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.valid_through.description',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'application_url' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.application_url',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['page', 'url', 'email'],
            ],
        ],
        'contact_email' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.contact_email',
            'config' => [
                'type' => 'email',
            ],
        ],
        'company' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.company',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.company.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_smartjobfinder_domain_model_company',
                'foreign_table_where' => 'AND {#tx_smartjobfinder_domain_model_company}.{#sys_language_uid} IN (-1,0) ORDER BY name',
                'default' => 0,
                'items' => [
                    ['label' => $lll . 'tx_smartjobfinder_domain_model_job.company.none', 'value' => 0],
                ],
            ],
        ],
        'requirements' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.requirements',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.requirements.description',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_smartjobfinder_domain_model_requirement',
                'foreign_field' => 'job',
                'foreign_sortby' => 'sorting',
                'maxitems' => 50,
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
            ],
        ],
        'benefits' => [
            'exclude' => true,
            'label' => $lll . 'tx_smartjobfinder_domain_model_job.benefits',
            'description' => $lll . 'tx_smartjobfinder_domain_model_job.benefits.description',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_smartjobfinder_domain_model_benefit',
                'foreign_field' => 'job',
                'foreign_sortby' => 'sorting',
                'maxitems' => 50,
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showAllLocalizationLink' => true,
                    'showSynchronizationLink' => true,
                    'enabledControls' => [
                        'info' => true,
                        'new' => true,
                        'dragdrop' => true,
                        'sort' => true,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ],
                ],
            ],
        ],
        'categories' => [
            'config' => [
                'type' => 'category',
            ],
        ],
    ],
];
