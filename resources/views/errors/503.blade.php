@extends('errors.minimal')

@section('title', lexicon('label_common_Error503Title', 'Service Unavailable'))
@section('code', '503')
@section('message', lexicon('msg_common_Error503Message', 'Service Unavailable'))