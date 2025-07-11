const { createApp, ref, computed, onMounted, nextTick } = Vue;

const EnvProfileManager = {
    props: {
        initialProfiles: {
            type: Array,
            default: () => []
        },
        initialEnvContent: {
            type: String,
            default: ''
        },
        apiBaseUrl: {
            type: String,
            required: true
        },
        defaultAppName: {
            type: String,
            default: 'Laravel'
        }
    },
    setup(props) {
        const profiles = ref(props.initialProfiles);
        const currentEnvContent = ref(props.initialEnvContent);
        const selectedProfileId = ref(null);
        const showCreateModal = ref(false);
        const newProfileName = ref('');
        const newProfileAppName = ref(props.defaultAppName);
        const loading = ref(false);
        const message = ref('');
        const messageType = ref('success');
        const isDarkMode = ref(false);
        let monacoEditor = null;

        const activeProfile = computed(() => {
            return profiles.value.find(p => p.is_active) || null;
        });

        const selectedProfile = computed(() => {
            if (!selectedProfileId.value) return null;
            return profiles.value.find(p => p.id === selectedProfileId.value) || null;
        });

        const toggleTheme = () => {
            isDarkMode.value = !isDarkMode.value;
            localStorage.setItem('env-profiles-theme', isDarkMode.value ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', isDarkMode.value);
            
            if (monacoEditor) {
                monacoEditor.updateOptions({
                    theme: isDarkMode.value ? 'vs-dark' : 'vs-light'
                });
            }
        };

        const initTheme = () => {
            const savedTheme = localStorage.getItem('env-profiles-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            isDarkMode.value = savedTheme ? savedTheme === 'dark' : prefersDark;
            document.documentElement.classList.toggle('dark', isDarkMode.value);
        };

        const initMonacoEditor = () => {
            require(['vs/editor/editor.main'], function() {
                const container = document.getElementById('monaco-editor');
                if (!container) return;

                monacoEditor = monaco.editor.create(container, {
                    value: currentEnvContent.value,
                    language: 'plaintext',
                    theme: isDarkMode.value ? 'vs-dark' : 'vs-light',
                    automaticLayout: true,
                    minimap: { enabled: false },
                    fontSize: 14,
                    wordWrap: 'on',
                    scrollBeyondLastLine: false,
                    renderWhitespace: 'selection',
                    tabSize: 2
                });

                monacoEditor.onDidChangeModelContent(() => {
                    currentEnvContent.value = monacoEditor.getValue();
                });
            });
        };

        const showMessage = (msg, type = 'success') => {
            message.value = msg;
            messageType.value = type;
            setTimeout(() => {
                message.value = '';
            }, 5000);
        };

        const fetchProfiles = async () => {
            try {
                const response = await fetch(props.apiBaseUrl);
                const data = await response.json();
                profiles.value = data.profiles;
                currentEnvContent.value = data.current_env;
                if (monacoEditor) {
                    monacoEditor.setValue(data.current_env);
                }
            } catch (error) {
                showMessage('Failed to fetch profiles', 'error');
            }
        };

        const saveAsProfile = async () => {
            if (!newProfileName.value.trim()) {
                showMessage('Please enter a profile name', 'error');
                return;
            }

            loading.value = true;
            try {
                const response = await fetch(props.apiBaseUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        name: newProfileName.value,
                        app_name: newProfileAppName.value,
                        content: currentEnvContent.value,
                        is_active: false
                    })
                });

                if (!response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Failed to create profile');
                }

                await fetchProfiles();
                showCreateModal.value = false;
                newProfileName.value = '';
                newProfileAppName.value = props.defaultAppName;
                showMessage('Profile created successfully');
            } catch (error) {
                showMessage(error.message || 'Failed to create profile', 'error');
            } finally {
                loading.value = false;
            }
        };

        const overwriteEnv = async () => {
            if (!confirm('Are you sure you want to overwrite the current .env file?')) {
                return;
            }

            loading.value = true;
            try {
                const response = await fetch(`${props.apiBaseUrl}/current-env`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        content: currentEnvContent.value
                    })
                });

                if (!response.ok) {
                    throw new Error('Failed to update .env file');
                }

                showMessage('.env file updated successfully');
            } catch (error) {
                showMessage('Failed to update .env file', 'error');
            } finally {
                loading.value = false;
            }
        };

        const loadProfile = async () => {
            if (!selectedProfileId.value) return;

            const profile = selectedProfile.value;
            if (!profile) return;

            currentEnvContent.value = profile.content;
            if (monacoEditor) {
                monacoEditor.setValue(profile.content);
            }
            showMessage(`Loaded profile: ${profile.name}`);
        };

        const activateProfile = async (profileId) => {
            if (!confirm('This will overwrite your current .env file. Continue?')) {
                return;
            }

            loading.value = true;
            try {
                const response = await fetch(`${props.apiBaseUrl}/${profileId}/activate`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to activate profile');
                }

                await fetchProfiles();
                showMessage('Profile activated successfully');
            } catch (error) {
                showMessage('Failed to activate profile', 'error');
            } finally {
                loading.value = false;
            }
        };

        const openCreateModal = () => {
            showCreateModal.value = true;
            newProfileName.value = '';
            newProfileAppName.value = props.defaultAppName;
        };

        const deleteProfile = async (profileId) => {
            if (!confirm('Are you sure you want to delete this profile?')) {
                return;
            }

            loading.value = true;
            try {
                const response = await fetch(`${props.apiBaseUrl}/${profileId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to delete profile');
                }

                await fetchProfiles();
                if (selectedProfileId.value === profileId) {
                    selectedProfileId.value = null;
                }
                showMessage('Profile deleted successfully');
            } catch (error) {
                showMessage('Failed to delete profile', 'error');
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            initTheme();
            nextTick(() => {
                initMonacoEditor();
            });
            
            // Listen for theme changes from navbar
            window.addEventListener('theme-changed', (event) => {
                isDarkMode.value = event.detail.isDark;
                if (monacoEditor) {
                    monacoEditor.updateOptions({
                        theme: isDarkMode.value ? 'vs-dark' : 'vs-light'
                    });
                }
            });
        });

        return {
            profiles,
            currentEnvContent,
            selectedProfileId,
            showCreateModal,
            newProfileName,
            newProfileAppName,
            loading,
            message,
            messageType,
            activeProfile,
            selectedProfile,
            saveAsProfile,
            overwriteEnv,
            loadProfile,
            activateProfile,
            deleteProfile,
            openCreateModal,
            isDarkMode,
            toggleTheme
        };
    },
    template: `
        <div class="space-y-6">
            <!-- Message Alert -->
            <div v-if="message" 
                :class="[
                    'px-4 py-3 rounded-lg transition-all duration-300',
                    messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]">
                {{ message }}
            </div>

            <!-- Profile Management Bar -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-700 p-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Profile</label>
                        <select v-model="selectedProfileId" 
                                @change="loadProfile"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option :value="null">-- Select a profile --</option>
                            <option v-for="profile in profiles" 
                                    :key="profile.id" 
                                    :value="profile.id">
                                {{ profile.name }} {{ profile.is_active ? '(Active)' : '' }}
                            </option>
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button @click="openCreateModal"
                                :disabled="loading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Save as Profile
                        </button>
                        <button @click="overwriteEnv"
                                :disabled="loading"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Overwrite .env
                        </button>
                    </div>
                </div>

                <!-- Active Profile Info -->
                <div v-if="activeProfile" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-blue-800 dark:text-blue-300">
                        <strong>Active Profile:</strong> {{ activeProfile.name }}
                    </p>
                </div>
            </div>

            <!-- Monaco Editor -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Environment Configuration</h2>
                <div id="monaco-editor" class="monaco-editor-container"></div>
            </div>

            <!-- Profile List -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm dark:shadow-gray-700 p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Saved Profiles</h2>
                <div v-if="profiles.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
                    No profiles saved yet
                </div>
                <div v-else class="space-y-2">
                    <div v-for="profile in profiles" 
                         :key="profile.id"
                         class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div>
                            <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ profile.name }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400" v-if="profile.app_name">{{ profile.app_name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Updated: {{ new Date(profile.updated_at).toLocaleString() }}
                                <span v-if="profile.is_active" class="ml-2 text-green-600 font-medium">(Active)</span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button @click="selectedProfileId = profile.id; loadProfile()"
                                    class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                Load
                            </button>
                            <button @click="activateProfile(profile.id)"
                                    :disabled="profile.is_active || loading"
                                    class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded hover:bg-green-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Activate
                            </button>
                            <button @click="deleteProfile(profile.id)"
                                    :disabled="loading"
                                    class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Profile Modal -->
            <div v-if="showCreateModal" 
                 class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                 @click.self="showCreateModal = false">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Create New Profile</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profile Name *</label>
                            <input v-model="newProfileName"
                                   type="text"
                                   placeholder="Enter profile name"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Application Name</label>
                            <input v-model="newProfileAppName"
                                   @keyup.enter="saveAsProfile"
                                   type="text"
                                   placeholder="Enter application name"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button @click="showCreateModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            Cancel
                        </button>
                        <button @click="saveAsProfile"
                                :disabled="!newProfileName.trim() || loading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Create Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `
};

document.addEventListener('DOMContentLoaded', () => {
    const app = document.getElementById('env-profiles-app');
    if (app) {
        createApp({
            components: {
                'env-profile-manager': EnvProfileManager
            }
        }).mount('#env-profiles-app');
    }
});