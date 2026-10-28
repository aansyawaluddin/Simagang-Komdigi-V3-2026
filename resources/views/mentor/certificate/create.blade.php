@extends('layouts.app')

@section('title', 'Penilaian Sertifikat Magang - Sistem Magang')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-blue-100 py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1
                        class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                        {{ $mode === 'edit' ? 'Edit Penilaian Sertifikat' : 'Penilaian Sertifikat Magang' }}
                    </h1>
                    <p class="text-sm sm:text-base text-gray-600">Berikan penilaian untuk penerbitan sertifikat magang</p>
                </div>
                <a href="{{ route('mentor.report.index') }}"
                    class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-500 text-white font-semibold rounded-xl hover:bg-gray-600 shadow-md hover:shadow-lg transition-all duration-300 text-sm sm:text-base whitespace-nowrap">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-md border border-blue-100 overflow-hidden">

                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-4 sm:px-6 py-4 sm:py-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                        @if ($selectedIntern && $selectedIntern->photo_path)
                            <img src="{{ url('storage/' . $selectedIntern->photo_path) }}"
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-full object-cover object-center border-3 border-white shadow-lg flex-shrink-0 aspect-square"
                                alt="{{ $selectedIntern->name }}" />
                        @else
                            <div
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-white flex items-center justify-center shadow-lg flex-shrink-0 aspect-square">
                                <i class="fas fa-user text-blue-600 text-lg sm:text-xl"></i>
                            </div>
                        @endif
                        <div class="text-white flex-1 min-w-0">
                            <h2 class="text-lg sm:text-xl font-bold truncate">
                                {{ $selectedIntern ? $selectedIntern->name : 'Tidak ada' }}</h2>
                            <p class="text-blue-100 text-xs sm:text-sm">Penilaian untuk Sertifikat Magang</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-6 lg:p-8">
                    <form method="POST"
                        action="{{ $mode === 'edit'
                            ? route('mentor.certificates.update', $certificate->id)
                            : route('mentor.certificates.store') }}">
                        @csrf
                        @if ($mode === 'edit')
                            @method('PUT')
                        @endif

                        <input type="hidden" name="intern_id" value="{{ $selectedIntern ? $selectedIntern->id : '' }}">

                        <div class="mb-6 sm:mb-8">
                            <label for="issue_date" class="block text-sm font-bold text-gray-700 mb-2 sm:mb-3">
                                <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                                Tanggal Terbit Sertifikat
                            </label>
                            <input type="date" name="issue_date" id="issue_date" required
                                class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                value="{{ old('issue_date', $certificate?->issue_date?->format('Y-m-d') ?? date('Y-m-d')) }}">
                        </div>

                        <div
                            class="mb-6 sm:mb-8 p-3 sm:p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border-2 border-blue-200">
                            <div class="flex flex-col sm:flex-row items-start gap-3">
                                <i class="fas fa-info-circle text-blue-600 text-lg sm:text-xl mt-0.5 flex-shrink-0"></i>
                                <div class="w-full">
                                    <p class="font-bold text-blue-900 mb-2 text-sm sm:text-base">Kriteria Penilaian:</p>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-xs sm:text-sm">
                                        <span
                                            class="px-2 py-1 bg-green-100 text-green-800 rounded font-semibold text-center">A
                                            = 85-100</span>
                                        <span
                                            class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded font-semibold text-center">B+
                                            = 80-84</span>
                                        <span
                                            class="px-2 py-1 bg-blue-100 text-blue-800 rounded font-semibold text-center">B
                                            = 70-79</span>
                                        <span
                                            class="px-2 py-1 bg-cyan-100 text-cyan-800 rounded font-semibold text-center">C+
                                            = 65-69</span>
                                        <span
                                            class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded font-semibold text-center">C
                                            = 60-64</span>
                                        <span
                                            class="px-2 py-1 bg-orange-100 text-orange-800 rounded font-semibold text-center">D
                                            = 50-59</span>
                                        <span class="px-2 py-1 bg-red-100 text-red-800 rounded font-semibold text-center">E
                                            = 0-49</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Referensi nilai tugas Workspace (hanya bila modul Workspace aktif) --}}
                        <x-workspace.task-score-reference :intern-id="$selectedIntern?->id" class="mb-6 sm:mb-8" />

                        <div class="space-y-4 sm:space-y-6">
                            <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-clipboard-list text-blue-600 mr-2 sm:mr-3"></i>
                                Komponen Penilaian
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                                <div>
                                    <label for="discipline_attendance"
                                        class="block text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-user-clock text-green-600 mr-1.5"></i>
                                        Disiplin dan Kehadiran
                                    </label>
                                    <input type="number" name="discipline_attendance" id="discipline_attendance"
                                        min="0" max="100" required
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="0 - 100"
                                        value="{{ old('discipline_attendance', $certificate?->score?->discipline_attendance ?? ($selectedIntern->attendance_percentage ?? '')) }}">
                                </div>

                                <div>
                                    <label for="responsibility"
                                        class="block text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-tasks text-purple-600 mr-1.5"></i>
                                        Tanggung Jawab
                                    </label>
                                    <input type="number" name="responsibility" id="responsibility" min="0"
                                        max="100" required
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="0 - 100"
                                        value="{{ old('responsibility', $certificate->score->responsibility ?? '') }}">
                                </div>

                                <div>
                                    <label for="teamwork_communication"
                                        class="block text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-users text-blue-600 mr-1.5"></i>
                                        Kerja Sama dan Komunikasi
                                    </label>
                                    <input type="number" name="teamwork_communication" id="teamwork_communication"
                                        min="0" max="100" required
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="0 - 100"
                                        value="{{ old('teamwork_communication', $certificate->score->teamwork_communication ?? '') }}">
                                </div>

                                <div>
                                    <label for="technical_skill"
                                        class="block text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-cogs text-orange-600 mr-1.5"></i>
                                        Keterampilan Teknik
                                    </label>
                                    <input type="number" name="technical_skill" id="technical_skill" min="0"
                                        max="100" required
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="0 - 100"
                                        value="{{ old('technical_skill', $certificate->score->technical_skill ?? '') }}">
                                </div>

                                <div>
                                    <label for="work_ethic" class="block text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-handshake text-teal-600 mr-1.5"></i>
                                        Etika dan Sikap Kerja
                                    </label>
                                    <input type="number" name="work_ethic" id="work_ethic" min="0" max="100"
                                        required
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="0 - 100"
                                        value="{{ old('work_ethic', $certificate->score->work_ethic ?? '') }}">
                                </div>

                                <div>
                                    <label for="initiative_creativity"
                                        class="block text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                        <i class="fas fa-lightbulb text-yellow-600 mr-1.5"></i>
                                        Inisiatif dan Kreativitas
                                    </label>
                                    <input type="number" name="initiative_creativity" id="initiative_creativity"
                                        min="0" max="100" required
                                        class="w-full px-3 sm:px-4 py-2 sm:py-3 border-2 border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="0 - 100"
                                        value="{{ old('initiative_creativity', $certificate->score->initiative_creativity ?? '') }}">
                                </div>
                            </div>

                            <div class="pt-4 sm:pt-6 border-t border-gray-200">
                                <label for="micro_skill" class="block text-xs sm:text-sm font-bold text-gray-700 mb-2">
                                    <i class="fas fa-graduation-cap text-indigo-600 mr-1.5"></i>
                                    Pengerjaan Micro Skill (Jumlah Course)
                                </label>
                                <input type="number" name="micro_skill" id="micro_skill" min="0" readonly
                                    class="w-full px-3 sm:px-4 py-2 sm:py-3 border-2 border-gray-200 bg-gray-100 rounded-xl shadow-sm text-sm"
                                    placeholder="Contoh: 12"
                                    value="{{ old('micro_skill', $certificate?->score?->micro_skill ?? $microSkillDefault ?? ($selectedIntern->micro_skill_completed ?? '')) }}">
                                <p class="mt-2 text-xs text-gray-500">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Nilai ini otomatis diisi berdasarkan jumlah Micro Skill yang dikerjakan (tidak dapat diedit)
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200">
                            @if ($mode === 'edit' && $certificate && $certificate->score)
                                <a href="{{ route('mentor.certificates.print', $certificate->id) }}" target="_blank"
                                    class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 sm:py-3 bg-yellow-500 text-white font-semibold rounded-xl hover:bg-yellow-600 shadow-md hover:shadow-lg transition-all duration-300 text-sm sm:text-base">
                                    <i class="fas fa-eye mr-2"></i>
                                    Lihat Sertifikat
                                </a>
                            @endif

                            <a href="{{ route('mentor.report.index') }}"
                                class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-500 text-white font-semibold rounded-xl hover:bg-gray-600 shadow-md hover:shadow-lg transition-all duration-300 text-sm sm:text-base">
                                <i class="fas fa-times mr-2"></i>
                                Batal
                            </a>

                            <button type="submit"
                                class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 sm:py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 shadow-md hover:shadow-lg transition-all duration-300 text-sm sm:text-base">
                                <i class="fas fa-save mr-2"></i>
                                {{ $mode === 'edit' ? 'Update Nilai' : 'Simpan Nilai' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-white rounded-xl shadow-md border border-blue-100 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row items-start gap-3 sm:gap-4">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 mb-2 text-sm sm:text-base">Informasi Penilaian</h3>
                        <ul class="text-xs sm:text-sm text-gray-600 space-y-1.5">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 flex-shrink-0 mt-0.5"></i>
                                <span>Semua komponen dinilai dalam skala 0-100</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 flex-shrink-0 mt-0.5"></i>
                                <span>Nilai akhir akan otomatis dihitung dari rata-rata komponen</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 flex-shrink-0 mt-0.5"></i>
                                <span>Sertifikat dapat dilihat setelah penilaian disimpan</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-500 flex-shrink-0 mt-0.5"></i>
                                <span>Pastikan semua nilai sudah sesuai sebelum menyimpan</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
