@extends('errors.minimal')

@section('title', lexicon('label_common_Error500Title', 'Server Error'))
@section('code', '500')
@section('message', lexicon('msg_common_Error500Message', 'Server Error'))