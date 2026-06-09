<?php

namespace App\Livewire\Client;

use App\Helpers\Alert;
use App\Models\Client\Client;
use App\Models\User;
use App\Repositories\Account\UserRepository;
use App\Repositories\Client\ClientMagicLinkRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CreateUser extends Component
{

    public $token;
    public $name;
    #[Validate('required', message: 'Email Harus Diisi', onUpdate: false)]
    #[Validate('email', message: "Format Email Tidak Sesuai", onUpdate: false)]
    public $email;
    #[Validate('required', message: 'Password Harus Diisi', onUpdate: false)]
    public $password;
    #[Validate('required', message: 'Ketik Ulang Password Harus Diisi', onUpdate: false)]
    public $retypePassword;


    public function mount()
    {
        if (!$this->token) {
            return redirect()->route('login');
        }
        $magicLink = ClientMagicLinkRepository::findBy([
            ['token', $this->token]
        ]);
        if (!$magicLink) {
            return redirect()->route('login');
        }
        if ($magicLink->used_at && $magicLink->email) {
            return redirect()->route('login');
        }
        $this->name = $magicLink->client->name;
    }

    public function store()
    {
        $this->validate();

        if ($this->password != $this->retypePassword) {
            Alert::fail($this, 'Register Gagal', 'Pengetikan Ulang Password Tidak Sama');
            return;
        }
        $user = UserRepository::findByEmail($this->email);
        if (!empty($user)) {
            Alert::fail($this, 'Register Gagal', 'Email Sudah Digunakan');
            return;
        }
        try {
            DB::beginTransaction();


            $magicLink = ClientMagicLinkRepository::findBy([
                ['token', $this->token]
            ]);

            $magicLink->update([
                'used_at' => now(),
                'email' => $this->email,
                'password' => $this->password,
            ]);

            $user = UserRepository::create([
                'client_id' => $magicLink->client_id,
                'name' => $this->name,
                'no_whatsapp' => $magicLink->client->phone,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'pic' => $magicLink->client->pic
            ]);

            $user->assignRole(User::ROLE_CLIENT);

            Auth::loginUsingId($user->id);
            $this->redirectRoute('login');

            DB::commit();

            Alert::information($this, 'Data berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::fail($this, "Gagal", $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.client.create-user');
    }
}
