<script lang="ts">
    let { message }: { message: string } = $props();
</script>

<div class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-600/20 ring-inset" role="alert">
    <p class="font-medium">Someone else edited this</p>
    <p class="mt-0.5 text-amber-800">{message}</p>
</div>
