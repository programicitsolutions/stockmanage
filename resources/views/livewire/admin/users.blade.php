<div>
    <x-page title="Users" description="Create staff accounts. Deactivating a user blocks login. Ledger rows they already posted stay in place.">
        <form wire:submit="save" class="rounded-2xl bg-white border border-slate-200 p-5 space-y-4 max-w-xl">
            <h2 class="font-semibold">Add user</h2>
            <div>
                <x-input-label for="name" value="Name" />
                <x-text-input wire:model="name" id="name" class="block mt-1 w-full" required />
            </div>
            <div>
                <x-input-label for="email" value="Email" />
                <x-text-input wire:model="email" id="email" type="email" class="block mt-1 w-full" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="password" value="Temporary password" />
                <x-text-input wire:model="password" id="password" type="password" class="block mt-1 w-full" required />
            </div>
            <div>
                <x-input-label for="role_slug" value="Role" />
                <select wire:model="role_slug" id="role_slug" class="mt-1 block w-full rounded-md border-gray-300">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}">{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="receives_daily_summary" class="rounded border-gray-300 text-teal-700">
                Receive the evening stock summary
            </label>
            <x-primary-button>Create user</x-primary-button>
        </form>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Daily summary</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($users as $user)
                        <tr>
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">{{ $user->role?->name }}</td>
                            <td class="px-4 py-3">
                                <button wire:click="toggleActive({{ $user->id }})" class="text-teal-800">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <button wire:click="toggleSummary({{ $user->id }})" class="text-teal-800">
                                    {{ $user->receives_daily_summary ? 'On' : 'Off' }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div>{{ $users->links() }}</div>
    </x-page>
</div>
