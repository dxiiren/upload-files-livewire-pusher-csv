<div class="w-full px-8 py-6 bg-white shadow border rounded-md ">

    <div
        x-data="{ isDragOver: false }"
        class="flex items-center justify-between border border-gray-400 rounded-md px-6 py-4 w-full transition duration-300 cursor-pointer mb-4"
        :class="isDragOver ? 'bg-green-400 border-green-500' : 'hover:bg-green-100 hover:border-green-400'"
        @dragover.prevent="isDragOver = true"
        @dragleave.prevent="isDragOver = false"
        @drop.prevent="
            isDragOver = false;
            const files = $event.dataTransfer.files;
            if (files.length > 0) {
                $refs.fileInput.files = files;
                $refs.fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        "
        @click="$refs.fileInput.click()"
    >
        <p :class="isDragOver ? 'text-white' : 'text-gray-700'" class="text-sm font-medium select-none">
            Select file / Drag and drop
        </p>
    
        <button type="button"
                class="px-4 py-2 bg-white text-sm border border-gray-500 rounded hover:bg-gray-100"
                @click.stop="$refs.fileInput.click()">
            Upload File
        </button>
    
        <input type="file" wire:model="file" x-ref="fileInput" class="hidden" />
    </div>

    <div class="text-red-600 text-sm mb-4">@error('file') {{ $message }} @enderror</div>

    <div class="overflow-x-auto">
        <table class="min-w-full table-auto border-collapse border border-gray-300 text-sm " id="upload-table">
            <thead class="bg-gray-100 text-gray-700 uppercase">
                <tr>
                    <th class="border border-gray-300 px-6 py-3 text-left w-1/4">Time</th>
                    <th class="border border-gray-300 px-6 py-3 text-left w-1/3">File Name</th>
                    <th class="border border-gray-300 px-6 py-3 text-left w-1/3">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($uploads as $upload)
                    <tr class="hover:bg-gray-50">
                        <td class="border border-gray-300 px-6 py-4" data-order="{{ \Carbon\Carbon::parse($upload['created_at'])->timestamp }}">
                            {{ $upload['created_at'] }}<br>
                        
                            <span 
                                x-data="relativeTime('{{ \Carbon\Carbon::parse($upload['created_at'])->toISOString() }}')"
                                x-init="init()"
                                x-text="`(${display})`"
                                class="text-xs text-gray-500"
                            ></span>
                        </td>
                        <td class="border border-gray-300 px-6 py-4">{{ $upload['filename'] }}</td>
                        <td class="border border-gray-300 px-6 py-4 capitalize {{ $statusClasses[$upload['status']] ?? 'text-gray-600' }}">
                            {{ $upload['status'] }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="border border-gray-300 px-6 py-4 text-center text-gray-500">No uploads yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    // DataTables initialization
    document.addEventListener('DOMContentLoaded', function () {
        const hasData = document.querySelectorAll('#upload-table tbody tr').length > 0 &&
                        !document.querySelector('#upload-table tbody tr td[colspan]');

        if (hasData) {
            $('#upload-table').DataTable({
                paging: false,
                info: false,
                searching: false,
                ordering: true,
                responsive: true,
                order: [[0, 'desc']],
                columnDefs: [
                    { targets: 0, type: 'datetime' },
                ]
            });
        }
    });

    // Alpine.js component for relative time display
    document.addEventListener('alpine:init', () => {
        Alpine.data('relativeTime', (datetime) => ({
            time: new Date(datetime),
            display: '',
            update() {
                const now = new Date();
                const diffInSeconds = Math.floor((now - this.time) / 1000);

                if (diffInSeconds < 60) { // less than a minute
                    this.display = `${diffInSeconds} second${diffInSeconds !== 1 ? 's' : ''} ago`;
                } else if (diffInSeconds < 3600) { // less than an hour
                    const mins = Math.floor(diffInSeconds / 60);
                    this.display = `${mins} minute${mins !== 1 ? 's' : ''} ago`;
                } else if (diffInSeconds < 86400) { // less than a day
                    const hrs = Math.floor(diffInSeconds / 3600);
                    this.display = `${hrs} hour${hrs !== 1 ? 's' : ''} ago`;
                } else { // more than a day
                    this.display = new Intl.DateTimeFormat('en-MY', {
                        year: 'numeric', month: 'short', day: 'numeric',
                        hour: '2-digit', minute: '2-digit', second: '2-digit',
                        timeZone: 'Asia/Kuala_Lumpur'
                    }).format(this.time);
                }
            },
            init() {
                this.update();
                setInterval(() => this.update(), 1000);
            }
        }))
    });
</script>

<script>
    window.addEventListener('file-upload-status', event => {
        const { status, filename } = event.detail[0];

        const messages = {
            pending: `📤 Uploading ${filename}...`,
            processing: `🔄 Processing ${filename}...`,
            completed: `✅ Upload completed: ${filename}`,
            failed: `❌ Upload failed: ${filename}`
        };

        const types = {
            pending: 'info',
            processing: 'warning',
            completed: 'success',
            failed: 'error'
        };

        if (typeof toastr !== 'undefined') {
            toastr.options = {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
            };

            const type = types[status] || 'info';
            const message = messages[status] || 'Unknown status';

            if (typeof toastr[type] === 'function') {
                toastr[type](message);
            } else {
                console.warn(`Toastr method ${type} is not available`);
            }
        } else {
            console.warn('Toastr is not loaded');
        }
    });
</script>
