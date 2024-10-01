<!-- Modal Structure -->
<div id="cancelRequestModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Modal Content -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-6 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Cancel Request
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to cancel this request? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 sm:px-6 flex justify-between sm:justify-end space-x-3">
                <!-- No, Go Back Button without SVG and Form -->
                <form method="POST" id="noForm">
                    <button type="button" onclick="hideModal()" class="inline-flex justify-center items-center rounded-md border border-gray-300 shadow-sm px-4 py-2 w-28 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        No, Go Back
                    </button>
                </form>

                <!-- Yes, Cancel Button without SVG and Form -->
                <form method="POST" id="cancelForm">
                    @csrf
                    <button type="submit" class="inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 w-28 bg-blue-900 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Yes, Cancel
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
    function showCancelModal(requestId) {
        // Set the form action to cancel the specific request
        const form = document.getElementById('cancelForm');
        form.action = `/requests/cancel/${requestId}`;

        // Show the modal
        document.getElementById('cancelRequestModal').classList.remove('hidden');
    }

    function hideModal() {
        // Hide the modal
        document.getElementById('cancelRequestModal').classList.add('hidden');
    }
</script>
