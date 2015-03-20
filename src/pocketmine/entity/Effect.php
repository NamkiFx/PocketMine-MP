<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\entity;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\protocol\MobEffectPacket;
use pocketmine\Player;
use pocketmine\Server;

class Effect{
	const SPEED = 1;
	const SLOWNESS = 2;
	const SWIFTNESS = 3;
	const FATIGUE = 4;
	const MINING_FATIGUE = 4;
	//TODO: const STRENGTH = 5;
	//TODO: const HEALING = 6;
	//TODO: const HARMING = 7;
	const JUMP = 8;
	//const CONFUSION = 9;
	const REGENERATION = 10;
	//TODO: const DAMAGE_RESISTANCE = 11;
	const FIRE_RESISTANCE = 12;
	const WATER_BREATHING = 13;
	const INVISIBILITY = 14;
	//const BLINDNESS = 15;
	//const NIGHT_VISION = 16;
	//const HUNGER = 17;
	//TODO: const WEAKNESS = 18;
	const POISON = 19;
	const WITHER = 20;
	//const HEALTH_BOOST = 21;
	//const ABSORPTION = 22;
	//const SATURATION = 23;

	/** @var Effect[] */
	protected static $effects;

	public static function init(){
		self::$effects = new \SplFixedArray(256);

		self::$effects[Effect::SPEED] = new Effect(Effect::SPEED, "Speed", 124, 175, 198);
		self::$effects[Effect::SLOWNESS] = new Effect(Effect::SLOWNESS, "Slowness", 90, 108, 129, true);
		self::$effects[Effect::SWIFTNESS] = new Effect(Effect::SWIFTNESS, "Swiftness", 217, 192, 67);
		self::$effects[Effect::FATIGUE] = new Effect(Effect::FATIGUE, "Mining Fatigue", 74, 66, 23, true);
		//self::$effects[Effect::STRENGTH] = new Effect(Effect::STRENGTH, "Strength", 147, 36, 35);
		//self::$effects[Effect::HEALING] = new InstantEffect(Effect::HEALING, "Healing", 248, 36, 35);
		//self::$effects[Effect::HARMING] = new InstantEffect(Effect::HARMING, "Harming", 67, 10, 9, true);
		self::$effects[Effect::JUMP] = new Effect(Effect::JUMP, "Jump", 34, 255, 76);
		self::$effects[Effect::REGENERATION] = new Effect(Effect::REGENERATION, "Regeneration", 205, 92, 171);
		//self::$effects[Effect::DAMAGE_RESISTANCE] = new Effect(Effect::DAMAGE_RESISTANCE, "Damage Resistance", 153, 69, 58);
		self::$effects[Effect::FIRE_RESISTANCE] = new Effect(Effect::FIRE_RESISTANCE, "Fire Resistance", 228, 154, 58);
		self::$effects[Effect::WATER_BREATHING] = new Effect(Effect::WATER_BREATHING, "Water Breathing", 46, 82, 153);
		self::$effects[Effect::INVISIBILITY] = new Effect(Effect::INVISIBILITY, "Invisibility", 127, 131, 146);
		//self::$effects[Effect::WEAKNESS] = new Effect(Effect::WEAKNESS, "Weakness", 72, 77, 72 , true);
		self::$effects[Effect::POISON] = new Effect(Effect::POISON, "Poison", 78, 147, 49, true);
		self::$effects[Effect::WITHER] = new Effect(Effect::WITHER, "Wither", 53, 42, 39, true);
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	public static function getEffect($id){
		if(isset(self::$effects[$id])){
			return clone self::$effects[(int) $id];
		}
		return null;
	}

	public static function getEffectByName($name){
		if(defined(Effect::class . "::" . strtoupper($name))){
			return self::getEffect(constant(Effect::class . "::" . strtoupper($name)));
		}
		return null;
	}

	/** @var int */
	protected $id;

	protected $name;

	protected $duration;

	protected $amplifier;

	protected $color;

	protected $show = true;

	protected $ambient = false;

	protected $bad;

	public function __construct($id, $name, $r, $g, $b, $isBad = false){
		$this->id = $id;
		$this->name = $name;
		$this->bad = (bool) $isBad;
		$this->setColor($r, $g, $b);
	}

	public function getName(){
		return $this->name;
	}

	public function getId(){
		return $this->id;
	}

	public function setDuration($ticks){
		$this->duration = $ticks;
		return $this;
	}

	public function getDuration(){
		return $this->duration;
	}

	public function isVisible(){
		return $this->show;
	}

	public function setVisible($bool){
		$this->show = (bool) $bool;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getAmplifier(){
		return $this->amplifier;
	}

	/**
	 * @param int $amplifier
	 *
	 * @return $this
	 */
	public function setAmplifier($amplifier){
		$this->amplifier = (int) $amplifier;
		return $this;
	}

	public function isAmbient(){
		return $this->ambient;
	}

	public function setAmbient($ambient = true){
		$this->ambient = (bool) $ambient;
		return $this;
	}

	public function isBad(){
		return $this->bad;
	}

	public function canTick(){
		switch($this->id){
			case Effect::POISON:
				if(($interval = 25 >> $this->amplifier) > 0){
					return ($this->duration % $interval) === 0;
				}
				return true;
			case Effect::WITHER:
				if(($interval = 50 >> $this->amplifier) > 0){
					return ($this->duration % $interval) === 0;
				}
				return true;
			case Effect::REGENERATION:
				if(($interval = 40 >> $this->amplifier) > 0){
					return ($this->duration % $interval) === 0;
				}
				return true;
		}
		return false;
	}

	public function applyEffect(Entity $entity){
		switch($this->id){
			case Effect::POISON:
				if($entity->getHealth() > 1){
					$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, 1);
					$entity->attack($ev->getFinalDamage(), $ev);
				}
				break;

			case Effect::WITHER:
				$ev = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, 1);
				$entity->attack($ev->getFinalDamage(), $ev);
				break;

			case Effect::REGENERATION:
				if($entity->getHealth() < $entity->getMaxHealth()){
					$ev = new EntityRegainHealthEvent($entity, 1, EntityRegainHealthEvent::CAUSE_MAGIC);
					$entity->heal($ev->getAmount(), $ev);
				}
				break;
		}
	}

	public function getColor(){
		return [$this->color >> 16, ($this->color >> 8) & 0xff, $this->color & 0xff];
	}

	public function setColor($r, $g, $b){
		$this->color = (($r & 0xff) << 16) + (($g & 0xff) << 8) + ($b & 0xff);
	}

	public function add(Entity $entity, $modify = false){
		$pk = new MobEffectPacket();
		$pk->eid = $entity->getId();
		$pk->effectId = $this->getId();
		$pk->amplifier = $this->getAmplifier();
		$pk->particles = $this->isVisible();
		$pk->duration = $this->getDuration();
		if($modify){
			$pk->eventId = MobEffectPacket::EVENT_MODIFY;
		}else{
			$pk->eventId = MobEffectPacket::EVENT_ADD;
		}

		Server::broadcastPacket($entity->getViewers(), $pk);
		if($entity instanceof Player){
			$entity->dataPacket($pk);
		}

		if($this->id === Effect::INVISIBILITY){
			$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, true);
			$entity->setDataProperty(Entity::DATA_SHOW_NAMETAG, Entity::DATA_TYPE_BYTE, 0);
		}
	}

	public function remove(Entity $entity){
		$pk = new MobEffectPacket();
		$pk->eid = $entity->getId();
		$pk->eventId = MobEffectPacket::EVENT_REMOVE;
		$pk->effectId = $this->getId();
		Server::broadcastPacket($entity->getViewers(), $pk);
		if($entity instanceof Player){
			$entity->dataPacket($pk);
		}

		if($this->id === Effect::INVISIBILITY){
			$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_INVISIBLE, false);
			$entity->setDataProperty(Entity::DATA_SHOW_NAMETAG, Entity::DATA_TYPE_BYTE, 1);
		}
	}
}
