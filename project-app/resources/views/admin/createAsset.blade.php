@extends('layouts.app')

<style>
    /* Hides spinners in Chrome, Edge, and Safari */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Hides spinners in Firefox */
    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>


@section('header')
    <div class="headerTitle">
        <h2 class="font-semibold text-xl uppercase font-bold text-gray-800 leading-tight">
            create Asset
        </h2>
    </div>
@endsection

@section('content')
    <div class="contents">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <form action="{{ route('adminasset.create') }}" method="post" class="flex flex-col relative"
            enctype="multipart/form-data">
            @csrf

            <div class="formbox">
                <div
                    class="formInformation min-md:flex min-md:flex-row md:grid md:grid-cols-[auto_auto] md:grid-rows-[auto,auto] gap-4">
                    <div class="imgContainer flex flex-col w-full h-full items-center space-y-4 col-start-2">
                        <label class="font-semibold text-xs sm:text-sm md:text-base text-gray-700">Asset Image</label>
                        <div class="imageField w-40 h-40 border-2 border-gray-200 rounded-lg shadow-md overflow-hidden">
                            <img src="{{ asset('images/no-image.png') }}" id="imagePreview" alt="Asset Image"
                                class="w-full h-full object-cover">
                        </div>
                        <label for="image" class="text-blue-500 cursor-pointer hover:underline">
                            Select New Image
                            <x-text-input type="file" id="image" name="asst_img" class="hidden" />
                        </label>
                    </div>
                    <div class="formFields flex flex-col gap-2 md:row-start-1 md:col-start-1">
                        <div class="form-group">
                            <x-input-label for='assetname' class="font-regular p-1">Asset Name</x-input-label>
                            <x-text-input id="assetname" name='assetname'
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        </div>
                        <div class="form-group">
                            <x-input-label for="department" class="font-regular p-1">Department</x-input-label>
                            <select id="department" name="department" onchange="fetchDepartmentData(this.value)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}" @if ($loop->first) selected @endif>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grpInline grid md:grid-cols-2 max-md:grid-cols-1 gap-4">
                            <div class="form-group">
                                <x-input-label for='pCost' class="font-regular p-1">Purchase Cost</x-input-label>
                                <x-text-input id="pCost" name="pCost" type="number" step="0.01" min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                            <div class="form-group">
                                <x-input-label for='pDate' class="font-regular p-1">Purchase Date</x-input-label>
                                <x-text-input type="date" id="pDate" name='purchasedDate'
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                        </div>
                        <div class="grpInline grid md:grid-cols-2 max-md:grid-cols-1 gap-4">
                            <div class="form-group">
                                <x-input-label for='lifespan' class="font-regular p-1">Lifespan (Years)</x-input-label>
                                <x-text-input type="number" id="lifespan" name='lifespan' min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                            <div class="form-group">
                                <x-input-label for='salvageValue' class="font-regular p-1">Salvage Value</x-input-label>
                                <x-text-input id="salvageValue" type="number" name="salvageValue" step="0.01"
                                    min="0"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                        </div>

                        <div class="form-group">
                            <x-input-label for='depreciation' class="font-regular p-1">Depreciation (Per
                                Year)</x-input-label>
                            <x-text-input type="number" id="depreciation" value="0.00" name='depreciation' readonly
                                class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        </div>

                        <div class="grpInline grid md:grid-cols-2 max-md:grid-cols-1 gap-4">
                            <div class="form-group">
                                <x-input-label for='category' class="font-regular p-1">Category</x-input-label>
                                <select name="category" id="category" {{ $location['locs']->isEmpty() ? 'disabled' : '' }}
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if ($location['locs']->isEmpty())
                                    <option value="" disabled selected >No data available</option>
                                @else
                                    @foreach ($categories['ctglist'] as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <x-input-label for='loc' class="font-regular p-1">Location</x-input-label>
                                <select name="loc" id="loc" {{ $location['locs']->isEmpty() ? 'disabled' : '' }}
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if ($location['locs']->isEmpty())
                                        <option value="" disabled selected>No data available</option>
                                    @else
                                    @foreach ($location['locs'] as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="grpInline grid md:grid-cols-2 max-md:grid-cols-1 gap-4">
                            <div class="form-group">
                                <x-input-label for='mod' class="font-regular p-1">Model</x-input-label>
                                <select name="mod" id="mod" {{ $model['mod']->isEmpty() ? 'disabled' : '' }}
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if ($model['mod']->isEmpty())
                                        <option value="" disabled selected>No data available</option>
                                    @else
                                        @foreach ($model['mod'] as $model)
                                            <option value="{{ $model->id }}">{{ $model->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div class="form-group">
                                <x-input-label for='mcft' class="font-regular p-1">Manufacturer</x-input-label>
                                <select name="mcft" id="mcft" {{ $manufacturer['mcft']->isEmpty() ? 'disabled' : '' }}
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if ($manufacturer['mcft']->isEmpty())
                                        <option value="" disabled selected>No data available</option>
                                    @else
                                        @foreach ($manufacturer['mcft'] as $manufacturer)
                                            <option value="{{ $manufacturer->id }}">{{ $manufacturer->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                            <div id="dynamic-fields" class="grpInline grid md:grid-cols-2 max-md:grid-cols-1 gap-4">
                                {{-- Dynamic fields will be loaded here --}}
                            </div>
                        </div>
                    </div>
                    {{-- divider --}}
                    {{-- <div class="dvdr h-full w-[3px] bg-blue-950/10 hidden md:block"></div> --}}
                    {{-- divider --}}
                    <div class="form-group images flex items-center flex-col AdditionalInfo h-full">
                        {{-- Addtional Information / custom Fields --}}
                        <div class="customFields flex flex-col w-full mt-4">
                            <div class="w-full text-[20px] capitalize font-bold">
                                Additional Information</div>
                            <div class="addInfo grid grid-col-2 w-full" id="field">
                                <div class="addInfoContainer w-full p-2 scroll-smooth">
                                    <div
                                        class="fieldSet mt-2 min-md:flex min-md:flex-row {{ isset($addInfos) ? 'md:grid md:grid-cols-[20%_80%]' : 'flex' }} gap-2">
                                        @if ($addInfos)
                                            @foreach ($addInfos as $key => $dataItem)
                                                <span
                                                    class="flex w-full h-full items-center font-regular p-1">{{ $dataItem->name }}</span>
                                                <input type="text" name="field[key][]" placeholder="key"
                                                    class="hidden" value="{{ $dataItem->name }}">
                                                <input type="{{ $dataItem->type }}"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    name="field[value][]" placeholder="value">
                                            @endforeach
                                        @else
                                            <span class="text-slate-400">
                                                {{ 'No additional for this Department' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="butn mt-2 w-full flex justify-center col-span-2 row-start-3">
                        <x-primary-button
                            class="bg-blue-900 text-slate-100 transition ease-in ease-out hover:text-slate-100  hover:bg-blue-700 ">Create
                            Asset</x-primary-button>
                    </div>
                </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('pDate').value = today;

            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('imagePreview');

            imageInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Depreciation Calculation Inputs
            const depreciationInput = document.getElementById('depreciation');
            const purchaseCostInput = document.getElementById('pCost');
            const salvageValueInput = document.getElementById('salvageValue');
            const lifespanInput = document.getElementById('lifespan');

            // Helper function to create error elements for inputs
            function createErrorElement(input) {
                let errorElement = input.nextElementSibling;
                if (!errorElement || !errorElement.classList.contains('error-message')) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message text-red-500 text-sm';
                    errorElement.style.display = 'none'; // Initially hidden
                    input.parentNode.appendChild(errorElement);
                }
                return errorElement;
            }

            const purchaseCostError = createErrorElement(purchaseCostInput);
            const salvageValueError = createErrorElement(salvageValueInput);
            const lifespanError = createErrorElement(lifespanInput);

            // Display and clear error functions
            function displayError(input, errorElement, message) {
                input.classList.add('border-red-500');
                errorElement.textContent = message;
                errorElement.style.display = 'block';
            }

            function clearError(input, errorElement) {
                input.classList.remove('border-red-500');
                errorElement.style.display = 'none';
            }

            // Validate input fields to prevent negative values
            function validateInput(input, errorElement) {
                if (parseFloat(input.value) < 0 || input.value.includes('-')) {
                    input.value = ''; // Clear invalid input
                    displayError(input, errorElement, 'Negative values are not allowed.');
                } else {
                    clearError(input, errorElement); // Clear error if valid
                }
            }

            // Add event listeners to restrict negative input
            [purchaseCostInput, salvageValueInput, lifespanInput].forEach(input => {
                const errorElement = createErrorElement(input);

                input.addEventListener('keypress', function(event) {
                    if (event.key === '-' || event.key === '+') {
                        event.preventDefault(); // Block minus and plus signs
                        displayError(input, errorElement, 'Negative values are not allowed.');
                    }
                });

                input.addEventListener('input', function() {
                    validateInput(input, errorElement);
                });

                input.addEventListener('paste', function(event) {
                    const clipboardData = event.clipboardData || window.clipboardData;
                    const pastedData = clipboardData.getData('text');

                    if (pastedData.includes('-') || parseFloat(pastedData) < 0) {
                        event.preventDefault(); // Block paste event
                        displayError(input, errorElement, 'Negative values are not allowed.');
                    }
                });
            });

            // Depreciation Calculation Logic
            function calculateDepreciation() {
                const cost = parseFloat(purchaseCostInput.value) || 0;
                const salvageValue = parseFloat(salvageValueInput.value) || 0;
                const lifespan = parseInt(lifespanInput.value) || 1;

                if (salvageValue > cost) {
                    displayError(salvageValueInput, salvageValueError,
                        'Salvage value cannot exceed the purchase cost.');
                    depreciationInput.value = "0.00";
                    return;
                } else {
                    clearError(salvageValueInput, salvageValueError);
                }

                if (lifespan > 0) {
                    const depreciation = (cost - salvageValue) / lifespan;
                    depreciationInput.value = depreciation.toFixed(2);
                } else {
                    depreciationInput.value = "0.00";
                }
            }

            // Attach event listeners for depreciation calculation
            purchaseCostInput.addEventListener('input', calculateDepreciation);
            salvageValueInput.addEventListener('input', calculateDepreciation);
            lifespanInput.addEventListener('input', calculateDepreciation);

            // Initialize input fields with default values
            purchaseCostInput.value = "";
            salvageValueInput.value = "";
            lifespanInput.value = "";
            depreciationInput.value = "0.00";
        });
    </script>

@endsection
