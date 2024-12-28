<script setup>
import { Input } from '@/components/ui/input/index.js';
import { Label } from '@/components/ui/label/index.js';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button/index.js';

const form = useForm({
  email: '',
  password: '',
});

const onSubmit = () => {
  // eslint-disable-next-line no-undef
  form.post(route('auth.login'));
};
</script>

<template>
  <Head>
    <title>Pr0verter - Login</title>
    <meta
      name="description"
      content="Der pr0verter ist ein Converter für das pr0gramm. Hier kannst Videos konvertieren." />
  </Head>
  <form class="grid w-full items-start gap-6" @submit.prevent="onSubmit">
    <h1 class="scroll-m-20 text-4xl font-extrabold tracking-tight lg:text-5xl">
      Login
    </h1>
    <fieldset class="grid gap-6 rounded-lg border p-4">
      <legend class="-ml-1 px-1 text-sm font-medium">
        Mit bestehendem Account einloggen
      </legend>
      <Label for="email" class="text-sm font-medium">
        E-Mail
        <Input
          id="email"
          v-model="form.email"
          type="email"
          class="mt-1"
          required />
      </Label>
      <Label for="password" class="text-sm font-medium">
        Passwort
        <Input
          id="password"
          v-model="form.password"
          type="password"
          class="mt-1"
          required />
      </Label>
      <span v-if="form.errors.email" class="text-red-600">{{
        form.errors.email
      }}</span>
      <Button type="submit" class="mt-4 w-full"> Login</Button>
    </fieldset>
  </form>
</template>

<style scoped></style>
