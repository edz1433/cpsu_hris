{{-- Leave Application / CTO Form switch on the employee's application page. --}}
<nav class="dtr-seg lv-form-tabs" aria-label="Application forms">
    <a href="#" data-lv-tab="leave" class="{{ $activeTab === 'cto' ? '' : 'is-active' }}"><i class="fas fa-umbrella-beach"></i> Leave Form</a>
    <a href="#" data-lv-tab="cto" class="{{ $activeTab === 'cto' ? 'is-active' : '' }}"><i class="fas fa-business-time"></i> CTO Form</a>
</nav>
