Delete AWS snapshots, keeping only the latest number of snapshots specified


## Requirements
- [AWS CLI](http://aws.amazon.com/cli/)
- AWS IAM snapshot permissions ([example policy](#example-iam-policy))
- PHP 5.3+
- Access to crontab (or some other job scheduler)

## Setup
This assumes you've already installed and setup [AWS CLI](http://aws.amazon.com/cli/) and added the correct IAM permissions within your AWS console.

### 1. Create PHP file to load class and hold snapshot configuration
```php
<?php
require_once('snapshots.php');
$volumes = array(
   'vol-123af85a' => array('keep' => 7)
);
$snapshots = new DeleteSnapshots($volumes);
$snapshots->run();
```
### 2. Add cron job
The cron job schedule will depend on your configuration. The class honors the interval setting, but you may not want it to run every minute of every day when you just need a nightly backup.
```bash
# run every night at 3:00 am
00	03	* * * /usr/bin/php /root/scripts/run-snapshots.php
```

## Volume Configuration

| Name | Type | Description |
|------|------|-------------|
| *volume id* | string | AWS EBS volume ID
| keep | integer | total number of snapshots to store for volume |


## Example IAM Policy
This is a minimal policy that includes ONLY the permissions needed to work. You could also limit the "Resources" option to restrict it even further.
```
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "ec2:DeleteSnapshot",
        "ec2:DescribeSnapshots"
      ],
      "Resource": [
        "*"
      ]
    }
  ]
}
```

## Fresh install on Ubuntu 14.04
```
sudo apt-get install python-pip php5-cli
sudo pip install awscli

// must set region - ie: us-east-1, us-west-1
aws configure
```
