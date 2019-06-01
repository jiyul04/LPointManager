<?php

namespace LPointManager\event;

use LPoint\event\LPointEvent;

class CreateAccountEvent extends LPointEvent{
	
	protected $player;
	
	public function __construct(string $player){
		$this->player = $player;
	}
	
	public function getPlayer() : string{
		return $this->player;
	}
	
}