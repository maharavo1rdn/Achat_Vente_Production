<template>
    <div class="page-container">
        <!-- Loading state -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p class="loading-text">Chargement...</p>
        </div>

        <!-- Content -->
        <div v-else class="content-wrapper">
            <!-- Header -->
            <div class="page-header fade-in" style="animation-delay: 0.1s">
                <div>
                    <h1 class="page-title">{{ isNew ? 'Nouvel Employé' : `Fiche - ${form.nom || ''}` }}</h1>
                    <p class="page-subtitle">{{ isNew ? 'Créer un nouvel employé' : 'Modifier les informations' }}</p>
                    <p v-if="isNew" class="text-sm text-gray-500">Le code employé sera généré automatiquement lors de la création.</p>
                </div>
                <div class="header-actions">
                    <router-link to="/personnel" class="btn-secondary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Retour</span>
                    </router-link>
                    <button class="btn-primary" @click="save" :disabled="saving">
                        <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <span>{{ saving ? 'Enregistrement...' : 'Enregistrer' }}</span>
                    </button>
                </div>
            </div>

            <!-- Form Card -->
            <div class="card fade-in" style="animation-delay: 0.15s">
                <div class="card-section">
                    <h3 class="section-title">Informations générales</h3>
                    <div class="form-grid">
                        <div class="form-group" v-if="!isNew">
                            <label class="label">Code Employé</label>
                            <input v-model="form.code_employe" class="input" placeholder="Ex: EMP001" readonly />
                        </div>

                        <div class="form-group">
                            <label class="label">Rôle *</label>
                            <select v-model="form.personnel_role_id" class="select">
                                <option value="">Sélectionner un rôle</option>
                                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.libelle }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label">Nom *</label>
                            <input v-model="form.nom" class="input" placeholder="Nom de famille" />
                        </div>

                        <div class="form-group">
                            <label class="label">Prénom *</label>
                            <input v-model="form.prenom" class="input" placeholder="Prénom" />
                        </div>

                        <div class="form-group">
                            <label class="label">Email *</label>
                            <input v-model="form.email" type="email" class="input" placeholder="email@exemple.com" />
                        </div>

                        <div class="form-group">
                            <label class="label">Téléphone</label>
                            <input v-model="form.telephone" class="input" placeholder="+261 34 12 345 67" />
                        </div>
                    </div>
                </div>

                <!-- Password Section (only for new) -->
                <div v-if="isNew" class="card-section">
                    <h3 class="section-title">Sécurité</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="label">Mot de passe *</label>
                            <input v-model="password" type="password" class="input" placeholder="Min. 6 caractères" />
                            <p v-if="passwordError" class="error-text">{{ passwordError }}</p>
                        </div>

                        <div class="form-group">
                            <label class="label">Confirmer mot de passe *</label>
                            <input v-model="confirmPassword" type="password" class="input"
                                placeholder="Retapez le mot de passe" />
                        </div>
                    </div>
                </div>

                <!-- Organization Section -->
                <div class="card-section">
                    <h3 class="section-title">Affectation</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="label">Entreprise *</label>
                            <select v-model="form.entreprise_id" class="select" @change="onEntrepriseChange">
                                <option value="">Sélectionner une entreprise</option>
                                <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label">Site par défaut</label>
                            <select v-model="form.site_defaut_id" class="select">
                                <option :value="null">Aucun</option>
                                <option v-for="s in sites" :key="s.id" :value="s.id">{{ s.nom }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label">Statut</label>
                            <select v-model="form.est_actif" class="select">
                                <option :value="true">Actif</option>
                                <option :value="false">Inactif</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="error-banner">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ error }}</span>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <router-link to="/personnel" class="btn-secondary">
                        Annuler
                    </router-link>
                    <button class="btn-primary" @click="save" :disabled="saving">
                        <svg v-if="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <div v-else class="spinner-small"></div>
                        <span>{{ saving ? 'Enregistrement...' : (isNew ? 'Créer l\'employé' : 'Mettre à jour') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import personnelService from '@/services/personnelService'
import entrepriseService from '@/services/entrepriseService'
import siteService from '@/services/siteService'

const route = useRoute()
const router = useRouter()
const id = route.params.id
const isNew = route.name === 'personnel-new'

const form = ref({
    code_employe: '',
    nom: '',
    prenom: '',
    email: '',
    telephone: '',
    est_actif: true,
    personnel_role_id: null,
    entreprise_id: null,
    site_defaut_id: null
})

const password = ref('')
const confirmPassword = ref('')
const passwordError = ref(null)

const roles = ref([])
const entreprises = ref([])
const sites = ref([])
const loading = ref(false)
const error = ref(null)
const saving = ref(false)

const loadRoles = async () => {
    try {
        const resp = await personnelService.getRoles()
        roles.value = resp.data || []
    } catch (err) {
        console.error('Erreur chargement roles', err)
    }
}

const loadEntreprises = async () => {
    try {
        const resp = await entrepriseService.getAll()
        entreprises.value = resp.data || []
    } catch (err) {
        console.error('Erreur chargement entreprises', err)
    }
}

const loadSites = async (entrepriseId) => {
    try {
        if (!entrepriseId) {
            sites.value = []
            return
        }
        const resp = await siteService.getByEntreprise(entrepriseId)
        sites.value = resp.data || []
    } catch (err) {
        console.error('Erreur chargement sites', err)
        sites.value = []
    }
}

const loadPersonnel = async (personnelId) => {
    loading.value = true
    try {
        const resp = await personnelService.getById(personnelId)
        form.value = resp.data || form.value
        if (form.value.entreprise_id) {
            await loadSites(form.value.entreprise_id)
        }
    } catch (err) {
        error.value = 'Impossible de charger le personnel'
        console.error(err)
    } finally {
        loading.value = false
    }
}

const onEntrepriseChange = async () => {
    await loadSites(form.value.entreprise_id)
    if (!sites.value.find(s => s.id === form.value.site_defaut_id)) {
        form.value.site_defaut_id = null
    }
}

const save = async () => {
    error.value = null
    passwordError.value = null
    saving.value = true

    // Client-side trim and basic validation
    form.value.nom = form.value.nom ? String(form.value.nom).trim() : ''
    form.value.prenom = form.value.prenom ? String(form.value.prenom).trim() : ''
    form.value.email = form.value.email ? String(form.value.email).trim() : ''

    if (!form.value.nom) {
        error.value = 'Le nom est obligatoire'
        saving.value = false
        return
    }
    if (!form.value.personnel_role_id) {
        error.value = 'Le rôle est obligatoire'
        saving.value = false
        return
    }

    try {
        if (isNew) {
            if (password.value && password.value.length < 6) {
                passwordError.value = 'Le mot de passe doit contenir au moins 6 caractères'
                saving.value = false
                return
            }
            if (password.value && password.value !== confirmPassword.value) {
                passwordError.value = 'Les mots de passe ne correspondent pas'
                saving.value = false
                return
            }

            const payload = { ...form.value }
            if (password.value) payload.mot_de_passe = password.value

            await personnelService.create(payload)
            alert('Personnel créé avec succès')
            router.push({ name: 'personnel' })
        } else {
            await personnelService.update(id, form.value)
            alert('Personnel mis à jour avec succès')
            router.push({ name: 'personnel' })
        }
    } catch (err) {
        console.error('Erreur sauvegarde personnel', err)
        // Prefer server error message when available
        error.value = err.response?.data?.error || err.response?.data?.message || 'Erreur lors de la sauvegarde'
    } finally {
        saving.value = false
    }
}

onMounted(async () => {
    await loadRoles()
    await loadEntreprises()
    if (!isNew) await loadPersonnel(id)
})
</script>

<style scoped>
.page-container {
    @apply min-h-screen ml-64 p-6 bg-gray-50;
}

@media (max-width: 1024px) {
    .page-container {
        @apply ml-20 p-4;
    }
}

@media (max-width: 768px) {
    .page-container {
        @apply ml-0 p-4;
    }
}

.loading-state {
    @apply flex flex-col items-center justify-center py-20;
}

.spinner {
    @apply w-12 h-12 border-4 border-gray-200 border-t-gray-900 rounded-full;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.loading-text {
    @apply mt-4 text-sm text-gray-600;
}

.content-wrapper {
    @apply space-y-6;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}

.page-header {
    @apply flex items-center justify-between mb-6;
}

.page-title {
    @apply text-2xl font-bold text-gray-900;
}

.page-subtitle {
    @apply text-sm text-gray-500 mt-1;
}

.header-actions {
    @apply flex items-center gap-3;
}

.card {
    @apply bg-white rounded-lg border border-gray-200 p-6;
}

.card-section {
    @apply pb-6 mb-6 border-b border-gray-100;
}

.card-section:last-of-type {
    @apply border-b-0 pb-0 mb-0;
}

.section-title {
    @apply text-base font-semibold text-gray-900 mb-4;
}

.form-grid {
    @apply grid grid-cols-1 md:grid-cols-2 gap-4;
}

.form-group {
    @apply space-y-2;
}

.label {
    @apply block text-xs font-medium text-gray-700;
}

.input {
    @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm bg-white;
}

.select {
    @apply w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 outline-none text-sm bg-white disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed;
}

.error-text {
    @apply text-xs text-red-600 mt-1;
}

.error-banner {
    @apply flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-600 mb-6;
}

.form-actions {
    @apply flex items-center justify-end gap-3 pt-6 border-t border-gray-100;
}

.btn-primary {
    @apply flex items-center gap-2 px-6 py-2.5 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-all text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
    @apply flex items-center gap-2 px-6 py-2.5 bg-white text-gray-900 border border-gray-300 rounded-lg hover:bg-gray-50 transition-all text-sm font-medium;
}

.spinner-small {
    @apply w-4 h-4 border-2 border-white border-t-transparent rounded-full;
    animation: spin 1s linear infinite;
}
</style>