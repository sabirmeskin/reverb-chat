<?php

use Livewire\Volt\Component;
use App\Models\User;

new class extends Component {
    public $contacts = [];

    public function mount()
    {
        $this->contacts = User::where('id', '!=', auth()->id())->get();
    }
}; ?>

<div>

    <flux:modal name="edit-profile" class="lg:w-96">

        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Ajouter un groupe</flux:heading>
                <flux:text class="mt-2">Choisissez les utilisateurs du groupe.</flux:text>
            </div>
            <div>

                    <div class="flex justify-between items-center">
                        <flux:heading>Contacts</flux:heading>
                    </div>

                    <flux:separator class="mt-2 mb-4" variant="subtle" />

                    <ul class="flex flex-col gap-3 overflow-y-scroll h-36">
                        <li class="flex items-center gap-2">
                            <flux:checkbox value="userid"  />
                            <flux:avatar size="xs" src="https://unavatar.io/github/calebporzio" />
                            <flux:heading>Caleb Porzio</flux:heading>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:checkbox value="userid"  />
                            <flux:avatar size="xs" src="https://unavatar.io/github/hugosaintemarie" />
                            <flux:heading>Hugo Sainte-Marie</flux:heading>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:checkbox value="userid"  />
                            <flux:avatar size="xs" src="https://unavatar.io/github/joshhanley" />
                            <flux:heading>Josh Hanley</flux:heading>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:avatar size="xs" src="https://unavatar.io/github/jasonlbeggs" />
                            <flux:heading>Jason Beggs</flux:heading>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:avatar size="xs" src="https://unavatar.io/github/joshhanley" />
                            <flux:heading>Josh Hanley</flux:heading>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:avatar size="xs" src="https://unavatar.io/github/joshhanley" />
                            <flux:heading>Josh Hanley</flux:heading>
                        </li>
                        <li class="flex items-center gap-2">
                            <flux:avatar size="xs" src="https://unavatar.io/github/joshhanley" />
                            <flux:heading>Josh Hanley</flux:heading>
                        </li>
                    </ul>

            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">Save changes</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
