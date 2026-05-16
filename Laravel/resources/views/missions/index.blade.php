<x-layouts::app :title="__('hub.districts.adventure_map.title')">
    @php($missionBoardRouteName = 'missions.index')

    @include('missions.partials.board')
</x-layouts::app>
