#
# Explicit schema for TYPO3 12. TYPO3 13/14 auto-generate columns from TCA;
# this file remains the source of truth for indexes and non-default types.
#

CREATE TABLE tx_smartjobfinder_domain_model_job
(
    title              varchar(255) DEFAULT '' NOT NULL,
    slug               varchar(2048),
    teaser             text,
    description        mediumtext,
    department         varchar(255) DEFAULT '' NOT NULL,
    location           varchar(255) DEFAULT '' NOT NULL,
    location_country   varchar(2)   DEFAULT 'DE' NOT NULL,
    employment_type    varchar(32)  DEFAULT 'FULL_TIME' NOT NULL,
    workplace_type     varchar(32)  DEFAULT 'ONSITE' NOT NULL,
    salary_min         int(11) DEFAULT '0' NOT NULL,
    salary_max         int(11) DEFAULT '0' NOT NULL,
    salary_currency    varchar(3)   DEFAULT 'EUR' NOT NULL,
    salary_interval    varchar(16)  DEFAULT 'YEAR' NOT NULL,
    valid_through      int(11) DEFAULT '0' NOT NULL,
    application_url    varchar(2048) DEFAULT '' NOT NULL,
    contact_email      varchar(255) DEFAULT '' NOT NULL,
    featured           smallint(5) unsigned DEFAULT '0' NOT NULL,
    company            int(11) unsigned DEFAULT '0' NOT NULL,
    requirements       int(11) unsigned DEFAULT '0' NOT NULL,
    benefits           int(11) unsigned DEFAULT '0' NOT NULL,
    notified_at        int(11) unsigned DEFAULT '0' NOT NULL,

    KEY slug (slug(80)),
    KEY company (company),
    KEY employment_type (employment_type),
    KEY workplace_type (workplace_type),
    KEY location (location),
    KEY featured_crdate (featured, crdate),
    KEY valid_through (valid_through),
    KEY notified_starttime (notified_at, starttime),
    FULLTEXT KEY fulltext_search (title, teaser, department, location)
);

CREATE TABLE tx_smartjobfinder_notification_log
(
    uid       int(11) unsigned NOT NULL auto_increment,
    pid       int(11) DEFAULT '0' NOT NULL,
    tstamp    int(11) unsigned DEFAULT '0' NOT NULL,
    crdate    int(11) unsigned DEFAULT '0' NOT NULL,
    job_uid   int(11) DEFAULT '0' NOT NULL,
    job_title varchar(255) DEFAULT '' NOT NULL,
    channel   varchar(32) DEFAULT '' NOT NULL,
    status    varchar(16) DEFAULT '' NOT NULL,
    payload   mediumtext,
    message   varchar(255) DEFAULT '' NOT NULL,
    PRIMARY KEY (uid),
    KEY job_uid (job_uid),
    KEY tstamp (tstamp)
);

CREATE TABLE tx_smartjobfinder_domain_model_company
(
    name        varchar(255) DEFAULT '' NOT NULL,
    website     varchar(2048) DEFAULT '' NOT NULL,
    description text,
    logo        int(11) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tx_smartjobfinder_domain_model_requirement
(
    job         int(11) unsigned DEFAULT '0' NOT NULL,
    title       varchar(255) DEFAULT '' NOT NULL,
    description text,

    KEY job (job)
);

CREATE TABLE tx_smartjobfinder_domain_model_benefit
(
    job         int(11) unsigned DEFAULT '0' NOT NULL,
    title       varchar(255) DEFAULT '' NOT NULL,
    description varchar(255) DEFAULT '' NOT NULL,

    KEY job (job)
);
