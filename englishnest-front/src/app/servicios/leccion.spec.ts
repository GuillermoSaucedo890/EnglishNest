import { TestBed } from '@angular/core/testing';

import { Leccion } from './leccion';

describe('Leccion', () => {
  let service: Leccion;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Leccion);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
