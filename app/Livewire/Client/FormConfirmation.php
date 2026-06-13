<?php

namespace App\Livewire\Client;

use App\Helpers\Alert;
use App\Models\Client\Client;
use App\Models\Client\ClientConfirmation;
use App\Models\User;
use App\Repositories\Account\UserRepository;
use App\Repositories\Client\ClientConfirmationRepository;
use App\Repositories\Client\ClientMagicLinkRepository;
use App\Repositories\Client\ClientRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class FormConfirmation extends Component
{
    public $name;
    public $claim_type;

    #[Validate('required', message: 'Status Penerimaan Nenkin 80% Harus Diisi', onUpdate: false)]
    public $nenkin_80_received_type;
    #[Validate('required', message: 'Posisi Saat Ini Harus Diisi', onUpdate: false)]
    public $current_position;
    public $back_to_jp_type;
    public $departure_type;
    public $departure;
    public $departure_month;
    public $departure_year;
    public $estimated_return;
    public $estimated_return_month;
    public $estimated_return_year;
    #[Validate('required', message: 'Alamat Indonesia Harus Diisi', onUpdate: false)]
    public $indonesia_address;
    #[Validate('required', message: 'NIK Harus Diisi', onUpdate: false)]
    public $nik;

    public function mount()
    {
        $confirmation = ClientConfirmationRepository::findBy([
            ['client_id', Auth::user()->client_id]
        ]);
        if ($confirmation) {
            return redirect()->route('login');
        }
        $this->name = UserRepository::find(Auth::id())->name;
        $this->claim_type = Client::CLAIM_TYPE_NORMAL;
    }

    public function updatedCurrentPosition($value)
    {
        if ($value === ClientConfirmation::CURRENT_POSITION_INDONESIA) {
            if ($this->back_to_jp_type === ClientConfirmation::BACK_TO_JP_TYPE_YES) {
                $this->estimated_return = null;
            } else {
                $this->estimated_return = null;
                $this->departure = null;
            }
        }
        if ($value === ClientConfirmation::CURRENT_POSITION_JEPANG) {
            $this->departure = null;
            $this->back_to_jp_type = null;
        }
    }

    public function updatedBackToJpType($value)
    {
        if ($value === ClientConfirmation::BACK_TO_JP_TYPE_YES) {
            $this->estimated_return = null;
        } else {
            $this->estimated_return = null;
            $this->departure = null;
        }
    }

    public function updated($a, $b)
    {

        consoleLog($this, [
            $this->current_position,
            $this->back_to_jp_type,
            $this->departure,

        ]);
        if (
            $this->current_position === ClientConfirmation::CURRENT_POSITION_INDONESIA &&
            $this->back_to_jp_type === ClientConfirmation::BACK_TO_JP_TYPE_YES &&
            $this->departure
        ) {
            $departure = Carbon::createFromFormat('Y-m', $this->departure);
            $is_less_then_7_month = $departure->greaterThanOrEqualTo(now()->addMonths(6));
            if (
                $is_less_then_7_month
            ) {
                $this->claim_type = 'ADJUSMNET';
            }
            if (
                !$is_less_then_7_month
            ) {
                $this->claim_type = 'NORMAL';
            }
        }

        if (
            $this->current_position === ClientConfirmation::CURRENT_POSITION_INDONESIA &&
            $this->back_to_jp_type === ClientConfirmation::BACK_TO_JP_TYPE_NO
        ) {
            $this->claim_type = 'SPEED';
        }
        if (
            $this->current_position === ClientConfirmation::CURRENT_POSITION_JEPANG
        ) {
            $this->claim_type = 'NORMAL';
        }
    }

    public function store()
    {
        $this->validate();
        try {
            if (
                !$this->current_position
            ) {
                // throw new \Exception('Estimasi Kepulangan Harus Di isi');
                $this->addError(
                    'current_position',
                    'Posisi Saat Ini Harus Diisi.'
                );

                return;
            }
            if (
                $this->current_position === ClientConfirmation::CURRENT_POSITION_JEPANG &&
                !$this->estimated_return
            ) {
                // throw new \Exception('Estimasi Kepulangan Harus Di isi');
                $this->addError(
                    'estimated_return',
                    'Estimasi Kepulangan Harus Diisi.'
                );

                return;
            }
            if (
                $this->current_position === ClientConfirmation::CURRENT_POSITION_INDONESIA &&
                !$this->back_to_jp_type
            ) {
                // throw new \Exception('Rencara Kembali Harus Di isi');
                $this->addError(
                    'back_to_jp_type',
                    'Rencana Kembali Harus Diisi.'
                );

                return;
            }
            if (
                $this->current_position === ClientConfirmation::CURRENT_POSITION_INDONESIA &&
                $this->back_to_jp_type === ClientConfirmation::BACK_TO_JP_TYPE_YES &&
                !$this->departure
            ) {
                // throw new \Exception('Estimasi Kembali Ke Jepang Harus Di isi');
                $this->addError(
                    'departure',
                    'Estimasi Kembali Ke Jepang Harus Diisi.'
                );

                return;
            }
            DB::beginTransaction();

            $is_less_then_7_month = false;
            if ($this->departure) {

                $departure = Carbon::createFromFormat('Y-m', $this->departure);
                $is_less_then_7_month = $departure->greaterThanOrEqualTo(now()->addMonths(6));
            }

            // consoleLog($this, [
            //     'nenkin_80_received_type' => $this->nenkin_80_received_type,
            //     'current_position' => $this->current_position,
            //     'back_to_jp_type' => $this->back_to_jp_type,
            //     'departure' => $this->departure,
            //     'departure_type' => $this->departure_type,
            //     'departure_month' => $this->departure ? Carbon::createFromFormat('Y-m', $this->departure)->format('m') : null,
            //     'departure_year' => $this->departure ? Carbon::createFromFormat('Y-m', $this->departure)->format('Y') : null,
            //     'is_less_than_7_month' => $is_less_then_7_month ? 'YA' : 'TIDAK',
            //     'estimated_return' => $this->estimated_return,
            //     'estimated_return_month' => $this->estimated_return ? Carbon::createFromFormat('Y-m', $this->estimated_return)->format('m') : null,
            //     'estimated_return_year' => $this->estimated_return ? Carbon::createFromFormat('Y-m', $this->estimated_return)->format('Y') : null,
            //     'indonesia_address' => $this->indonesia_address,
            //     'nik' => $this->nik,
            // ]);

            $client_confirmation = ClientConfirmation::firstOrCreate([
                'client_id' => Auth::user()->client_id,
            ], [
                'client_id' => Auth::user()->client_id,
                'nenkin_80_received_type' => $this->nenkin_80_received_type,
                'current_position' => $this->current_position,
                'back_to_jp_type' => $this->back_to_jp_type,
                'departure_month' => $this->departure ? Carbon::createFromFormat('Y-m', $this->departure)->format('m') : null,
                'departure_year' => $this->departure ? Carbon::createFromFormat('Y-m', $this->departure)->format('Y') : null,
                'estimated_return_month' => $this->estimated_return ? Carbon::createFromFormat('Y-m', $this->estimated_return)->format('m') : null,
                'estimated_return_year' => $this->estimated_return ? Carbon::createFromFormat('Y-m', $this->estimated_return)->format('Y') : null,
                'indonesia_address' => $this->indonesia_address,
                'nik' => $this->nik,
            ]);

            DB::commit();

            if (strtolower($this->claim_type) == Client::CLAIM_TYPE_NORMAL) {
                Alert::success($this, 'Berhasil', 'Tipe Pencairan Nenkin Anda Adalah ' . strtoupper($this->claim_type));
            } else {
                Alert::information($this, 'Data Berhasil Disimpan');
            }
            $this->dispatch('redirect-dashboard');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::fail($this, "Gagal", $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.client.form-confirmation');
    }
}
