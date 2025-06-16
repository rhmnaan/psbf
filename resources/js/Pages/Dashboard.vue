<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Tombol Buat Game -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg py-4 px-6">
                    <Link :href="route('games.store')" method="post" as="button"
                          class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-150">
                        Create Game
                    </Link>
                </div>

                <!-- Daftar Game Aktif -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg py-4 px-6">
                    <h3 class="text-lg font-semibold mb-4">Game Aktif</h3>

                    <ul class="divide-y">
                        <li v-for="game in games" :key="game.id" class="flex justify-between items-center px-2 py-2">
                            <span>
                                #{{ game.id }} by {{ game.player_one.name }}
                            </span>

                            <span v-if="game.player_one_id === page.props.auth.user.id"
                                  class="text-amber-600 font-semibold">
                                [My Room]
                            </span>

                            <Link v-if="game.player_one_id === page.props.auth.user.id && game.player_two_id === null"
                                  :href="`/games/${game.id}`" method="get" as="button"
                                  class="hover:bg-gray-100 transition-colors p-2 rounded-md">
                                Join Again
                            </Link>

                            <Link v-else :href="route('games.join', game)" method="post" as="button"
                                  class="hover:bg-gray-100 transition-colors p-2 rounded-md">
                                Join Game
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Riwayat Pertandingan -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg py-4 px-6">
                    <h3 class="text-lg font-semibold mb-4">Riwayat Pertandingan</h3>

                    <div class="max-h-64 overflow-y-auto border rounded-md">
                        <ul class="divide-y">
                            <li v-for="game in history" :key="game.id" class="px-4 py-2">
                                <div class="flex justify-between">
                                    <span>#{{ game.id }}: {{ game.player_one.name }} vs {{ game.player_two?.name ?? '??' }}</span>
                                    <span class="font-semibold text-green-600" v-if="game.winner_id === game.player_one_id">
                                        Pemenang: {{ game.player_one.name }}
                                    </span>
                                    <span class="font-semibold text-green-600" v-else-if="game.winner_id === game.player_two_id">
                                        Pemenang: {{ game.player_two?.name ?? '??' }}
                                    </span>
                                    <span class="font-semibold text-orange-500" v-else>
                                        Seri
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from "vue";

const page = usePage();
const props = defineProps(['games', 'history']);

const games = ref(props.games.data);
const history = ref(props.history);

// Realtime: user bergabung ke game
Echo.private('lobby')
    .listen('GameJoined', (event) => {
        games.value = games.value.filter((game) => game.id !== event.game.id);

        if (games.value.length < 5) {
            router.reload({ only: ['games'], onSuccess: () => games.value = props.games.data });
        }
    });

// Realtime: game baru dibuat
onMounted(() => {
    Echo.channel('games')
        .listen('.GameCreated', (event) => {
            const alreadyExists = games.value.some(game => game.id === event.id);
            if (!alreadyExists) {
                games.value.unshift({
                    id: event.id,
                    player_one_id: event.player_one_id,
                    player_one: { name: event.player_one },
                    state: event.state,
                    player_two_id: null,
                    status: false
                });
            }
        });
});
</script>
