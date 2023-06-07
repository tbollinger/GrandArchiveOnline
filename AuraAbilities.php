<?php

function PlayAura($cardID, $player, $number = 1, $isToken = false, $rogueHeronSpecial = false)
{
  global $CS_NumAuras;
  $otherPlayer = ($player == 1 ? 2 : 1);
  if(CardType($cardID) == "T") $isToken = true;
  if(DelimStringContains(CardSubType($cardID), "Affliction")) {
    $otherPlayer = $player;
    $player = ($player == 1 ? 2 : 1);
  }
  $auras = &GetAuras($player);
  $myHoldState = AuraDefaultHoldTriggerState($cardID);
  if($myHoldState == 0 && HoldPrioritySetting($player) == 1) $myHoldState = 1;
  $theirHoldState = AuraDefaultHoldTriggerState($cardID);
  if($theirHoldState == 0 && HoldPrioritySetting($otherPlayer) == 1) $theirHoldState = 1;
  for($i = 0; $i < $number; ++$i) {
    array_push($auras, $cardID);
    array_push($auras, 2); //Status
    if($rogueHeronSpecial) array_push($auras, 0); //Only happens on the damage effect of the Heron Master in the Roguelike Gamemode
    else array_push($auras, AuraPlayCounters($cardID)); //Miscellaneous Counters
    array_push($auras, 0); //Attack counters
    array_push($auras, ($isToken ? 1 : 0)); //Is token 0=No, 1=Yes
    array_push($auras, AuraNumUses($cardID));
    array_push($auras, GetUniqueId());
    array_push($auras, $myHoldState); //My Hold priority for triggers setting 2=Always hold, 1=Hold, 0=Don't hold
    array_push($auras, $theirHoldState); //Opponent Hold priority for triggers setting 2=Always hold, 1=Hold, 0=Don't hold
  }
  if(DelimStringContains(CardSubType($cardID), "Affliction")) IncrementClassState($otherPlayer, $CS_NumAuras, $number);
  else if($cardID != "ELE111") IncrementClassState($player, $CS_NumAuras, $number);
}

function AuraNumUses($cardID)
{
  switch ($cardID) {
    case "EVR140": case "EVR141": case "EVR142": case "EVR143":
    case "UPR005":
      return 1;
    default:
      return 0;
  }
}

function TokenCopyAura($player, $index)
{
  $auras = &GetAuras($player);
  PlayAura($auras[$index], $player, 1, true);
}

function PlayMyAura($cardID)
{
  global $currentPlayer;
  PlayAura($cardID, $currentPlayer, 1);
}

//Scope = Private
//Call DestroyAura to destroy an aura
function AuraDestroyed($player, $cardID, $isToken = false)
{
  $auras = &GetAuras($player);
  for($i = 0; $i < count($auras); $i += AuraPieces()) {
    switch($auras[$i]) {
      case "EVR141":
        if(!$isToken && $auras[$i + 5] > 0 && ClassContains($cardID, "ILLUSIONIST", $player)) {
          --$auras[$i + 5];
          PlayAura("MON104", $player);
        }
        break;
      case "DYN072":
        if($auras[$i] == $cardID) {
          $char = &GetPlayerCharacter($player);
          for($j = 0; $j < count($char); $j += CharacterPieces()) {
            if(CardSubType($char[$j]) == "Sword") $char[$j + 3] = 0;
          }
        }
        break;
      default: break;
    }
  }
  $goesWhere = GoesWhereAfterResolving($cardID);
  for($i = 0; $i < SearchCount(SearchAurasForCard("MON012", $player)); ++$i) {
    if(TalentContains($cardID, "LIGHT", $player)) $goesWhere = "SOUL";
    if(CardType($cardID) != "T" && $isToken) WriteLog("<span style='color:red;'>The card is not put in your soul from Merciful Retribution because it is a token copy.</span>");
    DealArcane(1, 0, "STATIC", "MON012", false, $player);
  }

  if(HasWard($cardID) && SearchCharacterActive($player, "DYN213") && !$isToken) {
    $char = &GetPlayerCharacter($player);
    $index = FindCharacterIndex($player, "DYN213");
    $char[$index + 1] = 1;
    GainResources($player, 1);
  }

  if(CardType($cardID) == "T" || $isToken) return; //Don't need to add to anywhere if it's a token
  switch($goesWhere) {
    case "GY":
      if(DelimStringContains(CardSubType($cardID), "Affliction")) {
        $player = ($player == 1 ? 2 : 1);
      } //Swap the player if it's an affliction
      AddGraveyard($cardID, $player, "PLAY");
      break;
    case "SOUL":
      AddSoul($cardID, $player, "PLAY");
      break;
    case "BANISH":
      BanishCardForPlayer($cardID, $player, "PLAY", "NA");
      break;
    default: break;
  }
}

function AuraLeavesPlay($player, $index)
{
  $auras = &GetAuras($player);
  $cardID = $auras[$index];
  $uniqueID = $auras[$index + 6];
  $otherPlayer = ($player == 1 ? 2 : 1);
  switch($cardID)
  {
    case "DYN221": case "DYN222": case "DYN223":
      $theirBanish = &GetBanish($otherPlayer);
      $banishIndex = -1;
      for($i=0; $i<count($theirBanish); $i+=BanishPieces()) {
        if($theirBanish[$i+1] == "DYN221-" . $uniqueID) $banishIndex = $i;
      }
      if($banishIndex > -1) {
        $banishCard = $theirBanish[$banishIndex];
        RemoveBanish($otherPlayer, $banishIndex);
        PlayAura($banishCard, $otherPlayer);
      }
      break;
    default: break;
  }
}

function AuraPlayCounters($cardID)
{
  switch ($cardID) {
    case "CRU075": return 1;
    case "EVR107": return 3;
    case "EVR108": return 2;
    case "EVR109": return 1;
    case "UPR140": return 3;
    default: return 0;
  }
}

function DestroyAuraUniqueID($player, $uniqueID)
{
  $index = SearchAurasForUniqueID($uniqueID, $player);
  if($index != -1) DestroyAura($player, $index, $uniqueID);
}

function DestroyAura($player, $index, $uniqueID="")
{
  $auras = &GetAuras($player);
  $isToken = $auras[$index + 4] == 1;
  if($uniqueID != "") $index = SearchAurasForUniqueID($uniqueID, $player);
  AuraDestroyAbility($player, $index, $isToken);
  $cardID = RemoveAura($player, $index);
  AuraDestroyed($player, $cardID, $isToken);
  return $cardID;
}

function AuraDestroyAbility($player, $index, $isToken)
{
  $auras = &GetAuras($player);
  $cardID = $auras[$index];
  switch($cardID)
  {
    case "EVR141":
      if(!$isToken && $auras[$index + 5] > 0 && ClassContains($cardID, "ILLUSIONIST", $player)) {
        --$auras[$index + 5];
        PlayAura("MON104", $player);
      }
      break;
    default: break;
  }
}

function RemoveAura($player, $index)
{
  AuraLeavesPlay($player, $index);
  $auras = &GetAuras($player);
  $cardID = $auras[$index];
  for($i = $index + AuraPieces() - 1; $i >= $index; --$i) {
    unset($auras[$i]);
  }
  $auras = array_values($auras);
  if(IsSpecificAuraAttacking($player, $index)) {
    CloseCombatChain();
  }
  return $cardID;
}

function AuraCostModifier()
{
  global $currentPlayer;
  $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
  $myAuras = &GetAuras($currentPlayer);
  $theirAuras = &GetAuras($otherPlayer);
  $modifier = 0;
  for($i = count($myAuras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    switch($myAuras[$i]) {
      case "ELE111":
        $modifier += 1;
        AddLayer("TRIGGER", $currentPlayer, "ELE111", "-", "-", $myAuras[$i + 6]);
        break;
      default: break;
    }
  }

  for($i = count($theirAuras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    switch($theirAuras[$i]) {
      case "ELE146":
        $modifier += 1;
        break;
      default:
        break;
    }
  }
  return $modifier;
}

// CR 2.1 - 4.2.1. Players do not get priority during the Start Phase.
// CR 2.1 - 4.3.1. The “beginning of the action phase” event occurs and abilities that trigger at the beginning of the action phase are triggered.
function AuraStartTurnAbilities()
{
  global $mainPlayer, $EffectContext;
  $auras = &GetAuras($mainPlayer);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $EffectContext = $auras[$i];
    switch ($auras[$i]) {

      default:
        break;
    }
  }
}


function AuraBeginEndPhaseTriggers()
{
  global $mainPlayer;
  $auras = &GetAuras($mainPlayer);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    switch($auras[$i]) {

      default: break;
    }
  }
  $auras = array_values($auras);
}

function AuraBeginEndPhaseAbilities()
{
  global $mainPlayer;
  global $CID_BloodRotPox, $CID_Inertia, $CID_Frailty;
  $auras = &GetAuras($mainPlayer);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $remove = 0;
    switch($auras[$i]) {

      default: break;
    }
    if($remove == 1) DestroyAura($mainPlayer, $i);
  }
  $auras = array_values($auras);
}

function ChannelTalent($index, $talent)
{
  global $mainPlayer;
  $auras = &GetAuras($mainPlayer);
  $toBottom = ++$auras[$index + 2];
  $numTalent = SearchCount(SearchPitch($mainPlayer, talent:$talent));
  if($toBottom <= $numTalent) {
    $cardName = CardName($auras[$index]);
    for($j = $toBottom; $j > 0; --$j) {
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYPITCH:talent=" . $talent, ($j == $toBottom ? 0 : 1));
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose $j card(s) to put on the bottom for " . $cardName, 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZADDZONE", $mainPlayer, "MYBOTDECK", 1);
      AddDecisionQueue("MZREMOVE", $mainPlayer, "-", 1);
    }
    AddDecisionQueue("ELSE", $mainPlayer, "-");
    AddDecisionQueue("PASSPARAMETER", $mainPlayer, "MYAURAS-" . $index, 1);
    AddDecisionQueue("MZDESTROY", $mainPlayer, "-", 1);
  } else {
    WriteLog(CardLink($auras[$index], $auras[$index]) . " was destroyed.");
    DestroyAura($mainPlayer, $index);
  }
}

function AuraEndTurnAbilities()
{
  global $CS_NumNonAttackCards, $mainPlayer, $CS_HitsWithSword;
  $auras = &GetAuras($mainPlayer);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $remove = false;
    switch($auras[$i]) {

      default: break;
    }
    if($remove) DestroyAura($mainPlayer, $i);
  }
}

function AuraEndTurnCleanup()
{
  $auras = &GetAuras(1);
  for($i = 0; $i < count($auras); $i += AuraPieces()) $auras[$i + 5] = AuraNumUses($auras[$i]);
  $auras = &GetAuras(2);
  for($i = 0; $i < count($auras); $i += AuraPieces()) $auras[$i + 5] = AuraNumUses($auras[$i]);
}

function AuraDamagePreventionAmount($player, $index)
{
  $auras = &GetAuras($player);
  switch($auras[$index])
  {
    case "ARC112": return (CountAura("CRU144", $player) > 0 ? 1 : 0);
    case "ARC167": return 4;
    case "ARC168": return 3;
    case "ARC169": return 2;
    case "MON104": return 1;
    case "UPR218": return 4;
    case "UPR219": return 3;
    case "UPR220": return 2;
    case "DYN217": return 1;
    case "DYN218": case "DYN219": case "DYN220": return 1;
    case "DYN221": case "DYN222": case "DYN223": return 1;
    default: break;
  }
}

//This function is for effects that prevent damage and DO destroy themselves
function AuraTakeDamageAbility($player, $index, $damage, $preventable)
{
  if($preventable) $damage -= AuraDamagePreventionAmount($player, $index);
  DestroyAura($player, $index);
  return $damage;
}

//This function is for effects that prevent damage and do NOT destroy themselves
//These are applied first and not prompted (which would be annoying because of course you want to do this before consuming something)
function AuraTakeDamageAbilities($player, $damage, $type)
{
  $auras = &GetAuras($player);
  $otherPlayer = $player == 1 ? 1 : 2;
  //CR 2.1 6.4.10f If an effect states that a prevention effect can not prevent the damage of an event, the prevention effect still applies to the event but its prevention amount is not reduced. Any additional modifications to the event by the prevention effect still occur.
  $preventable = CanDamageBePrevented($otherPlayer, $damage, $type);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    if($damage <= 0) {
      $damage = 0;
      break;
    }
    switch($auras[$i]) {
      case "CRU075":
        if($preventable) $damage -= 1;
        break;
      case "EVR131":
        if($type == "ARCANE" && $preventable) $damage -= 3;
        break;
      case "EVR132":
        if($type == "ARCANE" && $preventable) $damage -= 2;
        break;
      case "EVR133":
        if($type == "ARCANE" && $preventable) $damage -= 1;
        break;
      default: break;
    }
  }
  return $damage;
}


function AuraDamageTakenAbilities($player, $damage)
{
  $auras = &GetAuras($player);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $remove = 0;
    switch($auras[$i]) {
      case "ARC106": case "ARC107": case "ARC108": $remove = 1; break;
      case "EVR023": $remove = 1; break;
      default: break;
    }
    if($remove) DestroyAura($mainPlayer, $i);
  }
  return $damage;
}

function AuraLoseHealthAbilities($player, $amount)
{
  global $mainPlayer;
  $auras = &GetAuras($player);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $remove = 0;
    switch($auras[$i]) {
      default:
        break;
    }
    if($remove == 1) DestroyAura($player, $i);
  }
  return $amount;
}

function AuraPlayAbilities($attackID, $from="")
{
  global $currentPlayer, $CS_NumIllusionistActionCardAttacks;
  $auras = &GetAuras($currentPlayer);
  $cardType = CardType($attackID);
  $cardSubType = CardSubType($attackID);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $remove = 0;
    switch($auras[$i]) {

      default: break;
    }
    if($remove == 1) DestroyAura($currentPlayer, $i);
  }
}

function AuraAttackAbilities($attackID)
{
  global $combatChain, $mainPlayer, $CS_PlayIndex, $CS_NumIllusionistAttacks;
  $auras = &GetAuras($mainPlayer);
  $attackType = CardType($attackID);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $remove = 0;
    switch($auras[$i]) {
      case "ELE110":
        if($attackType == "AA") {
          WriteLog(CardLink($auras[$i], $auras[$i]) . " grants go again.");
          GiveAttackGoAgain();
          $remove = 1;
        }
        break;
      case "ELE226":
        if($attackType == "AA") DealArcane(1, 0, "PLAYCARD", $combatChain[0]);
        break;
      case "EVR140":
        if($auras[$i + 5] > 0 && DelimStringContains(CardSubtype($attackID), "Aura") && ClassContains($attackID, "ILLUSIONIST", $mainPlayer)) {
          WriteLog(CardLink($auras[$i], $auras[$i]) . " puts a +1 counter.");
          --$auras[$i + 5];
          ++$auras[GetClassState($mainPlayer, $CS_PlayIndex) + 3];
        }
        break;
      case "EVR142":
        if($auras[$i + 5] > 0 && ClassContains($attackID, "ILLUSIONIST", $mainPlayer) && GetClassState($mainPlayer, $CS_NumIllusionistAttacks) <= 1) {
          WriteLog(CardLink($auras[$i], $auras[$i]) . " makes your first illusionist attack each turn lose Phantasm.");
          --$auras[$i + 5];
          AddCurrentTurnEffect("EVR142", $mainPlayer, true);
        }
        break;
      case "UPR005":
        if($auras[$i + 5] > 0 && DelimStringContains(CardSubType($attackID), "Dragon")) {
          --$auras[$i + 5];
          DealArcane(1, 1, "STATIC", $attackID, false, $mainPlayer);
        }
        break;
      default: break;
    }
    if($remove == 1) DestroyAura($mainPlayer, $i);
  }
}

function AuraHitEffects($attackID)
{
  global $mainPlayer;
  $attackType = CardType($attackID);
  $attackSubType = CardSubType($attackID);
  $auras = &GetAuras($mainPlayer);
  for($i = count($auras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    $remove = 0;
    switch($auras[$i]) {
      case "ARC106": case "ARC107": case "ARC108":
        if($auras[$i] == "ARC106") $amount = 3;
        else if($auras[$i] == "ARC107") $amount = 2;
        else $amount = 1;
        if($attackType == "AA") {
          WriteLog(CardLink($auras[$i], $auras[$i]) . " created $amount runechants");
          PlayAura("ARC112", $mainPlayer, $amount);
          $remove = 1;
        }
        break;
      default: break;
    }
    if($remove == 1) DestroyAura($mainPlayer, $i);
  }
}

function AuraAttackModifiers($index)
{
  global $combatChain, $combatChainState, $CCS_AttackPlayedFrom;
  global $CID_Frailty;
  $modifier = 0;
  $player = $combatChain[$index + 1];
  $otherPlayer = ($player == 1 ? 2 : 1);
  $controlAuras = &GetAuras($player);
  for($i = 0; $i < count($controlAuras); $i += AuraPieces()) {
    switch($controlAuras[$i]) {
      case "ELE117":
        if(CardType($combatChain[$index]) == "AA") $modifier += 3;
        break;
      case $CID_Frailty:
        if(IsWeaponAttack() || $combatChainState[$CCS_AttackPlayedFrom] == "ARS") $modifier -= 1;
        break;
      default: break;
    }
  }
  $otherAuras = &GetAuras($otherPlayer);
  for($i = 0; $i < count($otherAuras); $i += AuraPieces()) {
    switch($otherAuras[$i]) {
      case "MON011":
        if(CardType($combatChain[$index]) == "AA") $modifier -= 1;
        break;
      default: break;
    }
  }
  return $modifier;
}

function NumNonTokenAura($player)
{
  $count = 0;
  $auras = &GetAuras($player);
  for($i = 0; $i < count($auras); $i += AuraPieces()) {
    if(CardType($auras[$i]) != "T") ++$count;
  }
  return $count;
}

function DestroyNumThisAura($player, $cardID, $num=1)
{
  $auras = &GetAuras($player);
  $count = 0;
  for($i = count($auras) - AuraPieces(); $i >= 0 && $count < $num; $i -= AuraPieces()) {
    if($auras[$i] == $cardID) {
      DestroyAura($player, $i);
      ++$count;
    }
  }
  return $count;
}

function GetAuraGemState($player, $cardID)
{
  global $currentPlayer;
  $auras = &GetAuras($player);
  $offset = ($currentPlayer == $player ? 7 : 8);
  $state = 0;
  for($i = 0; $i < count($auras); $i += AuraPieces()) {
    if($auras[$i] == $cardID && $auras[$i + $offset] > $state) $state = $auras[$i + $offset];
  }
  return $state;
}
