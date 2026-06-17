<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    role: Object,
    permissions: Array
});

const form = useForm({
    name: props.role.name || '',
    permissions: props.role.permissions.map(p => p.name) || []
});

const submit = () => {
    form.patch(route('admin.roles.update', props.role.id));
};
</script>

<template>
    <Head title="Edit Role" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Role: {{ role.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Role Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-gray-700">Assign Permissions</span>
                                <div class="mt-4 grid grid-cols-2 gap-4">
                                    <div v-for="permission in permissions" :key="permission.id" class="flex items-start">
                                        <div class="flex h-5 items-center">
                                            <input
                                                :id="'permission-' + permission.id"
                                                type="checkbox"
                                                :value="permission.name"
                                                v-model="form.permissions"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label :for="'permission-' + permission.id" class="font-medium text-gray-700">
                                                {{ permission.name }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.permissions" />
                            </div>

                            <div class="flex items-center justify-end">
                                <Link
                                    :href="route('admin.roles.index')"
                                    class="text-sm text-gray-600 hover:text-gray-900 mr-4"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Update Role
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
