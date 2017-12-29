<ul>
    <li>
        <em>{{ $line['result'] }}</em> <br>
        
        @if( !empty($line['bind']) )
            {{ $line['bind'] }}
        @endif
    </li>
    @each('JDM/one-result',$line['asks'],'line')
</ul>
