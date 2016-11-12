<?php
class DeleteSnapshots
{
	protected $volumes = null;

	protected $options = null;

	public function __construct($volumes)
	{
		$this->volumes = $volumes;
	}
	
	public function run()
	{
		foreach($this->volumes as $volume_id => $options)
		{
			if(!$this->setOptions($options)){
				echo 'Volume '.$volume_id.' not ran due to invalid config options'.PHP_EOL;
				continue;
			};
						
			// delete extra snapshots base on number of 'snapshots' option
			$this->deleteExtra($volume_id);
		}

		return true;
	}
	
	/**
	 * Delete snapshot
	 * @param  string $snapshot_id
	 * @return string
	 */
	public function delete($snapshot_id)
	{
		$cmd = sprintf('/usr/local/bin/aws ec2 delete-snapshot --snapshot-id %s',escapeshellarg($snapshot_id));
		return shell_exec($cmd);
	}
	
	/**
	 * Delete extra snapshots if $snapshot limit is met
	 * @param  string $volume_id
	 * @return string
	 */
	private function deleteExtra($volume_id)
	{
		$snapshots = $this->getSnapshots(array('volume-id'=>$volume_id));

		if( $snapshots !== false ){
			$snapshot_count = count($snapshots->Snapshots);
			
			if($snapshot_count <= $this->options['snapshots']) return false;
			
			for($x=0;$x<$snapshot_count - $this->options['snapshots']; ++$x)
			{
				$this->delete($snapshots->Snapshots[$x]->SnapshotId);
			}			
		}
	}
	
	/**
	 * Get list of snapshots based on filters
	 * @param  array $filters
	 * @return mixed  json object on true
	 */
	public function getSnapshots($filters=array())
	{
		$cmd_filters = false;
		foreach($filters as $name => $value) {
			$cmd_filters .= 'Name='.escapeshellarg($name).',Values='.escapeshellarg($value).' ';
		}

		$cmd = '/usr/local/bin/aws ec2 describe-snapshots '.($cmd_filters ? '--filters '.trim($cmd_filters) : '');
		$response = shell_exec($cmd);

		$snapshots = json_decode($response);
		if(!$snapshots) return false;

		// sort asc by date
		usort($snapshots->Snapshots, function($a,$b){
			return strtotime($a->StartTime) - strtotime($b->StartTime);
		});

		return $snapshots;
	}

	/**
	 * Sets volume options to current object
	 * @param  array $options
	 * @return boolean
	 */
	private function setOptions($options)
	{
		if(!isset($options['keep']) ) return false;

		$this->options = array(
			'snapshots'   => (int) $options['keep']
		);
		
		return true;
	}
}