<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    organization: Object
});

const form = useForm({
    name: props.organization.name || '',
    domain: props.organization.domain || ''
});

const submit = () => {
    form.patch(route('admin.organizations.update', props.organization.id));
};
</script>

<template>
    <Head title="Edit Organization" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Edit Organization: {{ organization.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Organization Name" />
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
                                <InputLabel for="domain" value="Domain (Optional)" />
                                <TextInput
                                    id="domain"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.domain"
                                    placeholder="e.g. envolutions.com"
                                />
                                <InputError class="mt-2" :message="form.errors.domain" />
                            </div>

                            <div class="flex items-center justify-end">
                                <Link
                                    :href="route('admin.organizations.index')"
                                    class="text-sm text-gray-600 hover:text-gray-900 mr-4"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Update Organization
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
