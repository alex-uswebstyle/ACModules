<?php

class IMilestoneTrackingImplementation extends ITrackingImplementation {
	/**
	 * Overwrites not used methods
	 */
	function logTime($value, IUser $user, JobType $job_type, DateValue $date, $billable_status = BILLABLE_STATUS_BILLABLE, IUser $by = null) {
	}

	function logExpense($value, IUser $user, ExpenseCategory $category, DateValue $date, $billable_status = BILLABLE_STATUS_BILLABLE, IUser $by = null) {
	}

	function getExpenses(User $user, $billable_status = null) {
	}

	function sumExpenses(User $user, $include_subobjects = false) {
	}

	function sumBillableExpenses(IUser $user, $include_subobjects = false) {
	}

	function getAddExpenseUrl() {
	}

	function getEstimatesUrl() {
	}

	function getSetEstimateUrl() {
	}

	function describeForApi(IUser $user, $detailed, &$result) {
	}

	function describe(IUser $user, $detailed, $for_interface, &$result) {
		$result = parent::describe($user, $detailed, $for_interface, &$result);
		unset($result['object_expenses']);
		return $result;
	}
	
	/**
	 * Cached last estimate calculated
	 * 
	 * @var Estimate
	 */
	private $cached_estimate = false;
	
	function getEstimate() {
		if($this->cached_estimate === false) {
			$estimate = parent::getEstimate();
			$this->is_autogenerated = $estimate ? false : true;
			$this->cached_estimate = $estimate ? $estimate : $this->getEstimateFromChilds();
		} // if
		
		return $this->cached_estimate;
	}

	/**
	 * Prende le stime dei figli, le somma e ne genera una singola
	 */
	function getEstimateFromChilds() {
		// Prendo tutti i figli della milestone corrente
		$tasks = Tasks::findByMilestone($this->object, STATE_VISIBLE);
		
		// Scorro tutti i figli e ne salvo le stime
		$estimates = array();
		$estimates_sum = 0; 
		if(is_foreachable($tasks)){
			foreach($tasks as $task){
				$estimate = Estimates::findLatestByParent($task);
				// Se non � stata impostata la stima ritorna nullo, quindi bisogna controllare che effettivamente l'oggetto esista
				if($estimate && $estimate instanceof Estimate){
					$estimates[] = $estimate;
					$estimates_sum+= $estimate->getValue();
				}
			}
		}

		// FIXME: seconda parte inutile
		if($this->object instanceof Milestone || $this->object instanceof RemediaMilestone) {
			$estimate = new Estimate();

			$estimate->setParent($this->object);
			$estimate->setValue($estimates_sum);
			$estimate->setJobType(JobTypes::findById(1)); // TODO: ho preso un job a caso, chissene
			$estimate->setComment('Stima generata automaticamente');
			$estimate->setCreatedBy($this->object->assignees()->getAssignee()); // Assegno come creatore un tizio tra gli assegnatari
			$estimate->setCreatedOn(DateTimeValue::now());
		}

		return $estimate;
	}
	
	function getEstimates() {
		return $this->getEstimate();
	}
	
	/**
	 * Tells if the estimate is calculated by summing all the childs or not
	 * @var unknown_type
	 */
	private $is_autogenerated = false;
	
	function isEstimateAutogenerated() {
		return $this->is_autogenerated;
	}
	
	/**
	 * Set estimated value
	 *
	 * @param float $value
	 * @param JobType $job_type
	 * @param string $comment
	 * @param IUser $by
	 * @param boolean $check_for_duplicate
	 * @return Estimate
	 */
	function setEstimate($value, JobType $job_type, $comment, IUser $by, $check_for_duplicate = true) {
		parent::setEstimate($value, $job_type, $comment, $by, $check_for_duplicate);
		$this->cached_estimate = parent::getEstimate();
	}

}