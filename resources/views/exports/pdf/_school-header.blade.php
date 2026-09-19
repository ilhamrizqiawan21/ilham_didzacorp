<div class="school-header">
    <div class="school-logo">
        <img src="{{ $reportSchool['logo'] }}" alt="Logo {{ $reportSchool['name'] }}">
    </div>
    <div class="school-identity">
        <div class="school-name">{{ $reportSchool['name'] }}</div>
        <div class="school-address">{{ $reportSchool['address'] }}</div>
        <div class="school-contact">
            @if($reportSchool['phone'])
                Telp. {{ $reportSchool['phone'] }}
            @endif
            @if($reportSchool['email'])
                {{ $reportSchool['phone'] ? ' | ' : '' }}Email: {{ $reportSchool['email'] }}
            @endif
            @if($reportSchool['website'])
                {{ ($reportSchool['phone'] || $reportSchool['email']) ? ' | ' : '' }}{{ $reportSchool['website'] }}
            @endif
        </div>
    </div>
</div>
<div class="header-line"></div>
