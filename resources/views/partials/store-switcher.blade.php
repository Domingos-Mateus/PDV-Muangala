@if ($stores->isNotEmpty())
    <form method="GET" class="mb-4">
        <select name="store_id" onchange="this.form.submit()" class="border rounded p-2 bg-white">
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected($s->id == $storeId)>{{ $s->name }}@if($s->address) - {{ $s->address }}@endif</option>
            @endforeach
        </select>
    </form>
@endif
