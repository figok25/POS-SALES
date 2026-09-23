{{--
    Pencarian Teks Interaktif pada Pemilihan Customer: pengganti <select>
    polos. Ketik nama/kode untuk memfilter, klik/tap untuk memilih --
    tetap submit sebagai input biasa (hidden input {{ $name }}), jadi
    controller & validasi penerima form TIDAK PERLU berubah sama sekali.

    Pakai Alpine.js yang sudah dimuat global lewat resources/js/app.js
    (dipakai juga di sales/transactions/create.blade.php) -- tidak ada
    library/CDN baru yang ditambahkan.

    Props:
    - name      : nama field yang dikirim ke server (mis. "customer_id")
    - options   : array/collection of ['id' => ..., 'label' => ...]
    - selected  : id yang sudah terpilih (opsional, untuk mode edit)
    - placeholder
    - required
--}}
@props(['name', 'options', 'selected' => null, 'placeholder' => 'Ketik nama atau kode...', 'required' => false])

<div
    x-data="{
        open: false,
        query: '',
        options: @js(collect($options)->values()),
        selectedId: @js($selected),
        get filtered() {
            if (!this.query) return this.options;
            const q = this.query.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(q));
        },
        get selectedLabel() {
            const found = this.options.find(o => String(o.id) === String(this.selectedId));
            return found ? found.label : '';
        },
        select(opt) {
            this.selectedId = opt.id;
            this.query = opt.label;
            this.open = false;
        },
        onFocus() {
            this.open = true;
            this.query = '';
        },
        onBlur() {
            setTimeout(() => {
                this.open = false;
                this.query = this.selectedLabel;
            }, 150);
        },
    }"
    x-init="query = selectedLabel"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" :value="selectedId">

    {{--
        required diletakkan di input TEKS (yang terlihat), bukan di hidden
        input di atas -- browser TIDAK memvalidasi atribut required pada
        input type=hidden sama sekali (dikecualikan dari HTML5 constraint
        validation), jadi kalau required cuma ditaruh di situ, validasi
        client-side akan diam-diam tidak pernah jalan. Ini tetap bukan
        validasi sempurna (user bisa mengetik teks tanpa benar-benar
        memilih opsi), makanya validasi server-side pada request tetap
        jadi sumber kebenaran akhir seperti sebelumnya.
    --}}
    <input type="text"
           x-model="query"
           @focus="onFocus()"
           @blur="onBlur()"
           @keydown.escape="open = false; query = selectedLabel"
           placeholder="{{ $placeholder }}"
           autocomplete="off"
           @if($required) required @endif
           {{ $attributes->merge(['class' => 'w-full border rounded px-3 py-2 text-sm']) }}>

    <div x-show="open" x-transition.opacity.duration.100ms
         class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border rounded shadow text-sm"
         style="display: none;">
        <template x-if="filtered.length === 0">
            <p class="px-3 py-2 text-gray-400">Tidak ditemukan.</p>
        </template>
        <template x-for="opt in filtered" :key="opt.id">
            <button type="button"
                    @mousedown.prevent="select(opt)"
                    class="block w-full text-left px-3 py-2 hover:bg-indigo-50"
                    :class="{ 'bg-indigo-50 font-medium': String(opt.id) === String(selectedId) }"
                    x-text="opt.label"></button>
        </template>
    </div>
</div>
