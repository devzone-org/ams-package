<?php

namespace Devzone\Ams\Http\Livewire\PettyExpenses;

use Devzone\Ams\Models\ChartOfAccount;
use Devzone\Ams\Models\PettyExpenses;
use Livewire\Component;
use Livewire\WithFileUploads;

class AddPettyExpenses extends Component
{
    use WithFileUploads;

    public $petty_expenses = [];

    protected $rules = [
        'petty_expenses.invoice_date' => 'required|date',
        'petty_expenses.name' => 'required',
        'petty_expenses.contact_no' => 'required|max:15',
        'petty_expenses.attachment' => 'nullable',
        'petty_expenses.account_head_id' => 'required|integer',
        'petty_expenses.amount' => 'required|numeric',
        'petty_expenses.description' => 'required',
    ];

    protected $validationAttributes = [
        'petty_expenses.invoice_date' => 'Invoice Date',
        'petty_expenses.name' => 'Name',
        'petty_expenses.contact_no' => 'Contact #',
        'petty_expenses.attachment' => 'Attachment',
        'petty_expenses.account_head_id' => 'Account Head',
        'petty_expenses.amount' => 'Amount',
        'petty_expenses.description' => 'Description',
    ];

    public $fetch_account_heads = [];
    public $success;
    public $is_edit = false;

    public function mount($id)
    {
        if (!empty($id)) {
            $found = PettyExpenses::find($id);
            if (empty($found)) {
                return $this->redirectTo = '/accounts/petty-expenses';
            }
            $this->petty_expenses = $found->toArray();
            unset($this->petty_expenses['created_at'], $this->petty_expenses['updated_at']);
            $this->is_edit = true;
        }
        $this->fetch_account_heads = ChartOfAccount::where('type', 'Expenses')->where('level', 5)->where('status', 't')->select('id', 'name')->get()->toArray();
    }


    public function save()
    {
        $this->validate();
        try {
            if (!empty($this->petty_expenses['attachment'])) {
                $this->petty_expenses['attachment'] = $this->petty_expenses['attachment']->storePublicly(config('app.aws_folder') . 'petty_expenses', 's3');
            }
            $exists = ChartOfAccount::where('id',$this->petty_expenses['account_head_id'])->exists();
            if (!$exists){
                throw new \Exception('Account Head not found.');
            }

            if (!$this->is_edit) {
                $this->petty_expenses['created_by'] = Auth::id();
                PettyExpenses::create($this->petty_expenses);
                $this->success = 'Record Created Successfully';
            } else {
                $this->petty_expenses['updated_by'] = Auth::id();
                $found = PettyExpenses::find($this->petty_expenses['id']);
                if (empty($found)) {
                    throw new \Exception('No Record Found.');
                }
                $found->update($this->petty_expenses);
                $this->success = 'Record Updated Successfully.';
            }
            $this->clear();
        } catch (\Exception $ex) {
            $this->addError('error', $ex->getMessage());
        }
    }

    public function clear()
    {
        $this->resetErrorBag();
        $this->petty_expenses = [];
    }


    public function render()
    {
        return view('ams::livewire.petty-expenses.add-petty-expenses');
    }
}