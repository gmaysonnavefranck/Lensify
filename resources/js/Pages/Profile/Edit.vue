<script setup lang="ts">
  import AuthLayout from '@/Layouts/AuthLayout.vue';
  import DeleteUserForm from './Partials/DeleteUserForm.vue';
  import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
  import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

  defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
  }>();

  defineOptions({ layout: AuthLayout });
</script>

<template>
  <Head title="Profile" />

  <div class="h-full overflow-y-auto">
    <div class="py-12">
      <div class="mx-auto max-w-3xl space-y-6 px-6">
        <h1 class="text-4xl font-bold text-textPrimary">Profile</h1>
        <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
          <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="max-w-xl" />
        </div>

        <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
          <UpdatePasswordForm class="max-w-xl" />
        </div>

        <div class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800">
          <DeleteUserForm class="max-w-xl" />
        </div>
      </div>
    </div>
  </div>
</template>
