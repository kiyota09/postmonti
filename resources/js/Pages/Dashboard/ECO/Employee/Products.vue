<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Plus, Search, Package, Image as ImageIcon, X, Edit2, Power } from 'lucide-vue-next';

const props = defineProps({
    products: Array,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);

// Create Form
const form = useForm({
    name: '',
    sku: '',
    category: '',
    price: '',
    stock: 0,
    status: 'draft',
    image: null,
});

// Edit Form
const editForm = useForm({
    id: null,
    name: '',
    sku: '',
    category: '',
    price: '',
    stock: 0,
    image: null,
});

const openEditModal = (product) => {
    editForm.id = product.id;
    editForm.name = product.name;
    editForm.sku = product.sku;
    editForm.category = product.category;
    editForm.price = product.price;
    editForm.stock = product.stock;
    showEditModal.value = true;
};

const submitCreate = () => {
    form.post(route('eco.employee.products.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const submitUpdate = () => {
    // Note: We use .post even for updates when sending files (images) in Laravel
    editForm.post(route('eco.employee.products.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const toggleStatus = (id) => {
    if (confirm("Change visibility status of this product?")) {
        router.patch(route('eco.employee.products.toggle', id), {}, {
            preserveScroll: true
        });
    }
};
</script>

<template>

    <Head title="Product Management" />
    <AuthenticatedLayout>
        <div class="max-w-[1600px] mx-auto p-10 space-y-8">
            <div class="flex justify-between items-center">
                <h1 class="text-4xl font-black uppercase tracking-tighter">Product <span
                        class="text-indigo-600">Architect</span></h1>
                <button @click="showCreateModal = true"
                    class="bg-indigo-600 text-white px-6 py-3 rounded-2xl font-black uppercase text-[10px] hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100">
                    <Plus class="inline h-4 w-4 mr-2" /> Add Product
                </button>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-gray-100 overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/50 text-[10px] font-black uppercase text-gray-400 tracking-widest">
                        <tr>
                            <th class="px-8 py-5 text-center">Preview</th>
                            <th class="px-8 py-5">Details</th>
                            <th class="px-8 py-5 text-center">Status</th>
                            <th class="px-8 py-5">Inventory</th>
                            <th class="px-8 py-5">Rate</th>
                            <th class="px-8 py-5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="product in products" :key="product.id"
                            :class="{ 'opacity-60 grayscale-[0.5] bg-gray-50/30': product.status === 'draft' }"
                            class="group transition-all">
                            <td class="px-8 py-6">
                                <div class="flex justify-center">
                                    <img v-if="product.image_path" :src="`/storage/${product.image_path}`"
                                        class="h-14 w-14 rounded-2xl object-cover border-2 border-white shadow-sm" />
                                    <div v-else
                                        class="h-14 w-14 bg-gray-100 rounded-2xl flex items-center justify-center text-gray-400">
                                        <ImageIcon class="h-6 w-6" />
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <p class="font-black uppercase text-sm tracking-tighter text-gray-900">{{ product.name
                                    }}</p>
                                <p class="text-[10px] font-mono font-bold text-indigo-500 uppercase">{{ product.sku }}
                                </p>
                                <p class="text-[9px] text-gray-400 uppercase font-bold tracking-widest mt-0.5">{{
                                    product.category }}</p>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span
                                    :class="product.status === 'published' ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-gray-100 text-gray-500 border-gray-200'"
                                    class="px-3 py-1 rounded-full text-[9px] font-black uppercase border">
                                    {{ product.status }}
                                </span>
                            </td>
                            <td class="px-8 py-6 font-bold text-gray-500 text-sm italic">{{ product.stock }} Units</td>
                            <td class="px-8 py-6 font-black text-indigo-600 italic">₱{{
                                parseFloat(product.price).toLocaleString() }}</td>
                            <td class="px-8 py-6 text-right">
                                <div class="flex justify-end gap-3">
                                    <button @click="openEditModal(product)"
                                        class="p-2.5 rounded-xl border border-gray-100 text-gray-400 hover:text-indigo-600 hover:border-indigo-100 transition-all">
                                        <Edit2 class="h-4 w-4" />
                                    </button>
                                    <button @click="toggleStatus(product.id)"
                                        :class="product.status === 'published' ? 'text-rose-400 hover:text-rose-600' : 'text-emerald-400 hover:text-emerald-600'"
                                        class="p-2.5 rounded-xl border border-gray-100 hover:bg-white transition-all">
                                        <Power class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-[2.5rem] w-full max-w-lg p-10 space-y-8 shadow-2xl border border-white/20">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-black uppercase italic tracking-tighter">New <span
                            class="text-indigo-600">Product</span></h2>
                    <button @click="showCreateModal = false"
                        class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <X class="h-6 w-6" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="grid grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Product Master
                            Name *</label>
                        <input v-model="form.name" type="text"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold focus:ring-indigo-500" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">SKU *</label>
                        <input v-model="form.sku" type="text"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Category *</label>
                        <input v-model="form.category" type="text"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Unit Rate (₱)
                            *</label>
                        <input v-model="form.price" type="number" step="0.01"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Initial Stock
                            *</label>
                        <input v-model="form.stock" type="number"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Preview
                            Image</label>
                        <input type="file" @input="form.image = $event.target.files[0]" accept="image/*"
                            class="w-full text-xs font-bold text-gray-400 mt-2" />
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="col-span-2 bg-indigo-600 text-white py-5 rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-indigo-700 transition-all">
                        {{ form.processing ? 'Synchronizing...' : 'Finalize Product' }}
                    </button>
                </form>
            </div>
        </div>

        <div v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-[2.5rem] w-full max-w-lg p-10 space-y-8 shadow-2xl border border-white/20">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-black uppercase italic tracking-tighter">Edit <span
                            class="text-indigo-600">Details</span></h2>
                    <button @click="showEditModal = false" class="p-2 hover:bg-gray-100 rounded-full transition-colors">
                        <X class="h-6 w-6" />
                    </button>
                </div>

                <form @submit.prevent="submitUpdate" class="grid grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Product Master
                            Name</label>
                        <input v-model="editForm.name" type="text"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold focus:ring-indigo-500" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">SKU</label>
                        <input v-model="editForm.sku" type="text"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Category</label>
                        <input v-model="editForm.category" type="text"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Unit Rate
                            (₱)</label>
                        <input v-model="editForm.price" type="number" step="0.01"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-1">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Stock
                            Level</label>
                        <input v-model="editForm.stock" type="number"
                            class="w-full rounded-2xl border-gray-100 bg-gray-50 p-4 text-sm font-bold" />
                    </div>
                    <div class="col-span-2">
                        <label class="text-[10px] font-black uppercase text-gray-400 tracking-widest">Update Image
                            (Optional)</label>
                        <input type="file" @input="editForm.image = $event.target.files[0]" accept="image/*"
                            class="w-full text-xs font-bold text-gray-400 mt-2" />
                    </div>
                    <button type="submit" :disabled="editForm.processing"
                        class="col-span-2 bg-indigo-600 text-white py-5 rounded-2xl font-black uppercase tracking-widest shadow-xl hover:bg-indigo-700 transition-all">
                        {{ editForm.processing ? 'Updating System...' : 'Update Record' }}
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>