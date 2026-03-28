<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const props = defineProps({
    client: Object,
});

const form = useForm({
    company_name: props.client.company_name,
    business_type: props.client.business_type,
    tin_number: props.client.tin_number,
    contact_person: props.client.contact_person,
    phone: props.client.phone,
    company_address: props.client.company_address,
    city: props.client.city,
    province: props.client.province,
    postal_code: props.client.postal_code,
    latitude: props.client.latitude,
    longitude: props.client.longitude,
});

const submit = () => {
    form.patch(route('client.profile.update'));
};
</script>

<template>

    <Head title="Business Profile" />
    <AuthenticatedLayout>
        <div class="max-w-3xl mx-auto p-4">
            <h1 class="text-2xl font-bold mb-6">Business Profile</h1>
            <form @submit.prevent="submit" class="space-y-4">
                <!-- fields -->
                <div>
                    <label>Company Name</label>
                    <input v-model="form.company_name" class="w-full border rounded p-2" required />
                </div>
                <!-- ... other fields ... -->
                <div>
                    <label>Address</label>
                    <textarea v-model="form.company_address" rows="3" class="w-full border rounded p-2"
                        required></textarea>
                </div>
                <div>
                    <label>Latitude</label>
                    <input v-model.number="form.latitude" step="any" class="w-full border rounded p-2" />
                </div>
                <div>
                    <label>Longitude</label>
                    <input v-model.number="form.longitude" step="any" class="w-full border rounded p-2" />
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Update Profile</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>